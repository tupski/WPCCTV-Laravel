<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Jobs\RetentionCleanupJob;
use App\Models\Setting;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Settings form. Writes go through the same whitelist the JSON endpoint uses:
 * `SettingController::WRITABLE_KEYS`, with the same forbidden-key guard and the
 * same retention field mapping. Only the VAPID *public* key is ever read, and
 * only for display.
 */
class SettingsViewController extends Controller
{
    public function __construct(protected ActivityLogService $activityLog) {}

    public function edit()
    {
        Gate::authorize('manage-settings');

        return view('admin.settings.edit', [
            'whatsappNumber' => Setting::get('whatsapp_number'),
            'vapidPublicKey' => $this->publicKey(),
            'retention' => $this->currentRetention(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $admin = $request->user();

        foreach (SettingController::FORBIDDEN_KEYS as $forbidden) {
            if ($request->has($forbidden)) {
                $this->activityLog->log('settings.forbidden_key_rejected', null, [
                    'key' => $forbidden,
                ], $admin);

                abort(422, 'Kunci pengaturan tidak diizinkan.');
            }
        }

        $data = $request->safe()->only(SettingController::WRITABLE_KEYS);
        $changed = [];

        if (array_key_exists('whatsapp_number', $data)) {
            Setting::set('whatsapp_number', $data['whatsapp_number'], $admin->id);
            $changed[] = 'whatsapp_number';
        }

        if (array_key_exists('retention', $data) && is_array($data['retention'])) {
            Setting::set('retention', $this->normalizeRetention($data['retention']), $admin->id);
            $changed[] = 'retention';
        }

        if ($changed !== []) {
            $this->activityLog->log('settings.updated', null, [
                'changed' => $changed,
            ], $admin);
        }

        return redirect()
            ->route('admin.settings-ui.edit')
            ->with('status', 'Pengaturan tersimpan.');
    }

    /**
     * Public key only. The private key lives in `.env` and is never read here.
     */
    protected function publicKey(): ?string
    {
        $key = Setting::get('vapaid_public_key') ?? config('vapaid.public_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * @return array<string, int>
     */
    protected function currentRetention(): array
    {
        $stored = array_merge(
            RetentionCleanupJob::DEFAULT_TIERS,
            (array) Setting::get('retention', []),
        );

        $result = [];

        foreach (SettingController::RETENTION_FIELD_MAP as $apiField => $storedField) {
            $result[$apiField] = (int) ($stored[$storedField] ?? RetentionCleanupJob::DEFAULT_TIERS[$storedField]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, int>
     */
    protected function normalizeRetention(array $input): array
    {
        $retention = [];

        foreach (SettingController::RETENTION_FIELD_MAP as $apiField => $storedField) {
            if (array_key_exists($apiField, $input)) {
                $retention[$storedField] = (int) $input[$apiField];
            }
        }

        return $retention;
    }
}
