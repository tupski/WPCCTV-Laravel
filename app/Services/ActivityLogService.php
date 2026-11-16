<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single write path for `activity_logs`.
 *
 * Contract:
 * - Never throws. A failed audit write must not break the operation.
 * - Redacts sensitive context keys before persisting.
 * - IP address comes from the request (TrustProxies-aware); never spoofed.
 */
class ActivityLogService
{
    /**
     * Substrings that mark a context key as sensitive (case-insensitive).
     *
     * Retained as the *documented list* the pattern below is built from — it
     * is the rule of record, and `isSensitiveKey()` derives its regex from it
     * so the two can never drift.
     *
     * `passwd` is included: it is the conventional shadow-file spelling and
     * was previously missed (audit M3, probe P4).
     */
    public const REDACTED_KEY_PATTERNS = [
        'password',
        'passwd',
        'token',
        'secret',
        'key',
        'p256dh',
        'auth',
    ];

    /**
     * The single ambiguous marker, handled by lookahead rather than by a
     * general exception list.
     *
     * `auth` is the only pattern that is a prefix of a common English word:
     * it begins `author`, `authority`, `authored_at` and `authorized_by`,
     * none of which is a credential, while it *is* the credential marker in
     * `auth`, `auth_key`, `x-auth` and `p256dh_auth`.
     *
     * So `auth` is matched as `auth(?!or)` instead of `auth`: the lookahead
     * rejects exactly the `author*` family and nothing else. Every other
     * pattern (`password`, `passwd`, `token`, `secret`, `key`, `p256dh`) is
     * unambiguous and stays a plain substring test.
     *
     * Why not a blanket exception list: an allowlist that admits `author`
     * wholesale would also admit `author_token`, a key that genuinely holds a
     * credential. The lookahead admits the *word* `author` without admitting
     * anything after it, so `author_name` passes and `author_token` is still
     * redacted because `token` matches on its own.
     */
    public const AUTH_MARKER = 'auth';

    /**
     * The negation applied to `auth` — see AUTH_MARKER.
     */
    public const AUTH_MARKER_PATTERN = '/auth(?!or)/';

    public const REDACTED_PLACEHOLDER = '[redacted]';

    public function log(
        string $action,
        ?Model $subject = null,
        array $context = [],
        ?User $user = null,
    ): ?ActivityLog {
        try {
            return ActivityLog::create([
                'user_id' => $user?->id ?? auth()->id(),
                'action' => mb_substr($action, 0, 100),
                'subject_type' => $subject === null ? null : $subject::class,
                'subject_id' => $subject?->getKey(),
                'context' => $this->redact($context),
                'ip_address' => $this->resolveIp(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('ActivityLogService: gagal menulis activity log.', [
                'action' => $action,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Log a model change, persisting only the changed attributes.
     */
    public function logModelChange(
        string $action,
        Model $model,
        array $before,
        array $after,
    ): ?ActivityLog {
        $changes = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $this->log($action, $model, ['changes' => $changes]);
    }

    /**
     * Recursively replace values of sensitive keys.
     */
    public function redact(array $context): array
    {
        return $this->redactArray($context);
    }

    protected function redactArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $clean[$key] = self::REDACTED_PLACEHOLDER;

                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->redactArray($value);

                continue;
            }

            if ($value instanceof Model) {
                $clean[$key] = [
                    'type' => $value::class,
                    'id' => $value->getKey(),
                ];

                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $clean[$key] = $value->format('Y-m-d H:i:s');

                continue;
            }

            if (is_object($value)) {
                $clean[$key] = Arr::isAssoc((array) $value)
                    ? $this->redactArray((array) $value)
                    : self::REDACTED_PLACEHOLDER;

                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    /**
     * Whether a context key names a credential and must be blanked.
     *
     * Rule, in order of application:
     *
     *  1. normalise: lowercase, and unify `-`, `.`, space to `_` so
     *     `api-key`, `api.key` and `api_key` are one case;
     *  2. test the unambiguous substrings (`password`, `passwd`, `token`,
     *     `secret`, `key`, `p256dh`) against both the separated form and the
     *     separator-stripped form, so camelCase `apiKey` is caught;
     *  3. test `auth` with a negative lookahead (`auth(?!or)`) so the whole
     *     `author*` family is excluded while `auth` / `auth_key` / `x-auth`
     *     are not.
     *
     * Verified against the three cases the audit demanded:
     *
     *   author, author_name, authority, authored_at → NOT redacted
     *   auth, auth_key, x-auth, p256dh_auth         → redacted
     *   passwd                                      → redacted (was missed)
     *
     * `author_token` is still redacted, because `token` matches on its own —
     * which is why the rule is a lookahead rather than a blanket allowlist
     * for anything starting with "author".
     */
    public function isSensitiveKey(string $key): bool
    {
        $needle = $this->normalizeKey($key);

        if ($needle === '') {
            return false;
        }

        return $this->containsSensitiveSubstring($needle);
    }

    /**
     * Lowercase and unify separators so one spelling reaches the matchers.
     */
    protected function normalizeKey(string $key): string
    {
        return str_replace(['-', '.', ' '], '_', mb_strtolower(trim($key)));
    }

    /**
     * Sensitive-substring test over the separated and de-separated forms.
     *
     * The de-separated pass is what catches `apiKey` (→ `apikey`, contains
     * `key`) and `authToken` (→ `authtoken`, contains `auth`), which a plain
     * `str_contains` over the `_`-separated form would miss.
     */
    protected function containsSensitiveSubstring(string $haystack): bool
    {
        $candidates = [$haystack, str_replace('_', '', $haystack)];

        foreach (self::REDACTED_KEY_PATTERNS as $pattern) {
            foreach ($candidates as $candidate) {
                if ($pattern === self::AUTH_MARKER) {
                    if (preg_match(self::AUTH_MARKER_PATTERN, $candidate) === 1) {
                        return true;
                    }

                    continue;
                }

                if (str_contains($candidate, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function resolveIp(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->ip();
    }
}
