<?php

namespace App\Support;

use Minishlink\WebPush\VAPID;
use Throwable;

/**
 * Generates a VAPID (EC P-256) keypair, trying every viable strategy in order.
 *
 * Why this exists (audit finding C1): the shipped command called
 * openssl_pkey_new() with a single config and returned a generic error when it
 * produced false. On hosts where OpenSSL has no reachable openssl.cnf,
 * *every* variant fails with:
 *
 *     error:80000003:system library::No such process
 *     error:10000080:BIO routines::no such file
 *     error:07000072:configuration file routines::no such file
 *
 * Supplying an explicit minimal config file makes the very same call succeed.
 * That is the primary strategy below. The library helper is a secondary
 * fallback; it can work on builds where the system config is present.
 *
 * The generator never returns a partially-formed keypair: a strategy either
 * yields a 65-byte public point / 32-byte private scalar, or it is rejected.
 */
class VapidKeyGenerator
{
    /** Uncompressed EC point: 0x04 || X (32) || Y (32). */
    public const PUBLIC_KEY_BYTES = 65;

    /** EC private scalar for P-256 (32 bytes). */
    public const PRIVATE_KEY_BYTES = 32;

    public function __construct(
        protected ?\Closure $pkeyNew = null,
        protected ?\Closure $libraryGenerate = null,
    ) {
        $this->pkeyNew ??= static fn (array $options) => openssl_pkey_new($options);
        $this->libraryGenerate ??= static fn (): array => VAPID::createVapidKeys();
    }

    /**
     * Generate a keypair.
     *
     * @return array{publicKey: string, privateKey: string} base64url-encoded
     *
     * @throws VapidGenerationException when every strategy fails
     */
    public function generate(): array
    {
        $diagnostics = [];

        foreach ($this->strategies() as $label => $strategy) {
            try {
                $keys = $strategy();

                if ($keys !== null) {
                    return $keys;
                }

                $diagnostics[$label] = $this->opensslErrors() ?: 'openssl_pkey_new() mengembalikan false tanpa pesan error.';
            } catch (Throwable $e) {
                $diagnostics[$label] = $e->getMessage();
            } finally {
                $this->clearErrors();
            }
        }

        throw new VapidGenerationException($diagnostics);
    }

    /**
     * Strategy label => callable returning a valid keypair or null.
     *
     * @return array<string, callable(): (array{publicKey: string, privateKey: string}|null)>
     */
    protected function strategies(): array
    {
        return [
            'explicit openssl.cnf (prime256v1)' => fn (): ?array => $this->viaExplicitConfig(),
            'openssl.cnf dari OPENSSL_CONF' => fn (): ?array => $this->viaEnvironmentConfig(),
            'minishlink VAPID::createVapidKeys()' => fn (): ?array => $this->viaLibrary(),
        ];
    }

    /**
     * Primary strategy: hand OpenSSL a minimal, guaranteed-readable config.
     *
     * This is the fix for the audited host. The config only needs a [req]
     * section; key generation itself does not consult any other stanza.
     */
    protected function viaExplicitConfig(): ?array
    {
        $configPath = tempnam(sys_get_temp_dir(), 'vapidcnf');

        if ($configPath === false) {
            return null;
        }

        try {
            file_put_contents($configPath, "[req]\ndistinguished_name = dn\nprompt = no\n[dn]\n");

            return $this->fromKeyResource(($this->pkeyNew)([
                'config' => $configPath,
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'prime256v1',
            ]));
        } finally {
            @unlink($configPath);
        }
    }

    /**
     * Secondary strategy: respect an operator-provided OPENSSL_CONF.
     */
    protected function viaEnvironmentConfig(): ?array
    {
        $configPath = getenv('OPENSSL_CONF');

        if (! is_string($configPath) || $configPath === '' || ! is_file($configPath)) {
            return null;
        }

        return $this->fromKeyResource(($this->pkeyNew)([
            'config' => $configPath,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]));
    }

    /**
     * Tertiary strategy: let minishlink do it (works when the system config
     * is reachable).
     */
    protected function viaLibrary(): ?array
    {
        $keys = ($this->libraryGenerate)();

        if (! is_array($keys)
            || ! is_string($keys['publicKey'] ?? null)
            || ! is_string($keys['privateKey'] ?? null)) {
            return null;
        }

        // The library already base64url-encodes the 65/32 byte forms.
        if ($this->b64urlDecode($keys['publicKey']) === null
            || $this->b64urlDecode($keys['privateKey']) === null) {
            return null;
        }

        return [
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ];
    }

    /**
     * Turn an OpenSSL key resource into the base64url pair web-push expects.
     */
    protected function fromKeyResource(mixed $key): ?array
    {
        if ($key === false || $key === null) {
            return null;
        }

        $details = openssl_pkey_get_details($key);

        if (! is_array($details) || ! isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            return null;
        }

        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $d = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        $publicKeyBytes = "\x04".$x.$y;

        // Reject anything that is not a canonical P-256 keypair rather than
        // writing a key the push service will reject at send time.
        if (strlen($publicKeyBytes) !== self::PUBLIC_KEY_BYTES
            || strlen($d) !== self::PRIVATE_KEY_BYTES) {
            return null;
        }

        return [
            'publicKey' => $this->b64url($publicKeyBytes),
            'privateKey' => $this->b64url($d),
        ];
    }

    /**
     * Drain and join the OpenSSL error queue (it is per-thread/per-process).
     */
    public function opensslErrors(): string
    {
        $errors = [];

        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return implode(' | ', array_unique($errors));
    }

    protected function clearErrors(): void
    {
        while (openssl_error_string() !== false) {
            // drain
        }
    }

    public function b64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public function b64urlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
