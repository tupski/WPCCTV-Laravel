<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\VapidGenerationException;
use App\Support\VapidKeyGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Minishlink\WebPush\VAPID;
use Tests\TestCase;

/**
 * Regression coverage for PHASE_4_CLOSURE_AUDIT.md C1.
 *
 * Root cause confirmed on the audited host: the OpenSSL extension loads and
 * reports 82 curves including prime256v1, but openssl_pkey_new() returns false
 * for every config variant because no openssl.cnf is reachable —
 * `error:07000072:configuration file routines::no such file`. Supplying an
 * explicit minimal config file makes the identical call succeed, which is the
 * strategy the generator now tries first.
 */
class VapidKeyGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The command must never leave push silently dead: it exits non-zero AND
     * prints the OpenSSL reason plus the documented offline alternative.
     */
    public function test_command_fails_loudly_with_actionable_message_when_it_cannot_generate(): void
    {
        $this->app->instance(VapidKeyGenerator::class, new class extends VapidKeyGenerator
        {
            public function __construct()
            {
                parent::__construct(
                    pkeyNew: static fn (array $options) => false,
                    libraryGenerate: static function (): array {
                        throw new \RuntimeException('Unable to create the key');
                    },
                );
            }

            public function opensslErrors(): string
            {
                return 'error:07000072:configuration file routines::no such file';
            }
        });

        $this->artisan('vapid:generate')
            ->expectsOutputToContain('Gagal membuat kunci VAPID')
            ->expectsOutputToContain('configuration file routines')
            ->expectsOutputToContain('npx web-push generate-vapid-keys')
            ->assertExitCode(1);

        $this->assertDatabaseCount('settings', 0);
    }

    /**
     * When generation succeeds, the command exits 0 and persists the public
     * key where the client-side subscriber reads it.
     */
    public function test_command_exits_zero_and_writes_config_when_generation_succeeds(): void
    {
        $original = app()->environmentFilePath();
        $tempDir = sys_get_temp_dir();
        $tempName = 'wpc_env_'.uniqid().'.env';
        $tempEnv = $tempDir.DIRECTORY_SEPARATOR.$tempName;
        File::put($tempEnv, "APP_NAME=WPCCTV\n");

        // environmentFilePath() is environmentPath() . DIRECTORY_SEPARATOR . environmentFile(),
        // so the directory and the file name are supplied separately.
        app()->useEnvironmentPath($tempDir)->loadEnvironmentFrom($tempName);

        try {
            $this->artisan('vapid:generate')
                ->expectsOutputToContain('Kunci VAPID berhasil dibuat.')
                ->assertExitCode(0);

            $written = File::get($tempEnv);
            $this->assertMatchesRegularExpression('/^VAPID_PUBLIC_KEY=\S+$/m', $written);
            $this->assertMatchesRegularExpression('/^VAPID_PRIVATE_KEY=\S+$/m', $written);

            $public = Setting::get('vapaid_public_key');
            $this->assertIsString($public);
            $this->assertSame(87, strlen($public), 'Uncompressed P-256 point is 65 bytes → 87 base64url chars.');

            $private = $this->extractEnvValue($written, 'VAPID_PRIVATE_KEY');
            $this->assertSame(43, strlen($private), 'P-256 scalar is 32 bytes → 43 base64url chars.');
        } finally {
            app()->useEnvironmentPath(dirname($original))->loadEnvironmentFrom(basename($original));
            File::delete($tempEnv);
        }
    }

    /**
     * The generator itself must produce a keypair the web-push library accepts.
     * This is the property the audit could not verify at all.
     */
    public function test_generated_keys_are_accepted_by_the_web_push_validator(): void
    {
        $keys = app(VapidKeyGenerator::class)->generate();

        $this->assertSame(87, strlen($keys['publicKey']));
        $this->assertSame(43, strlen($keys['privateKey']));

        // Throws on an invalid keypair; passing proves encode/pad/curve are right.
        VAPID::validate([
            'subject' => 'mailto:admin@wpcctv.id',
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ]);

        $this->assertSame(65, strlen(base64_decode(strtr($keys['publicKey'], '-_', '+/'))));
        $this->assertSame(32, strlen(base64_decode(strtr($keys['privateKey'], '-_', '+/'))));
    }

    /**
     * The diagnostic object must expose every attempted strategy, so an
     * operator can tell "lib unavailable" from "bad config".
     */
    public function test_generation_exception_reports_every_strategy(): void
    {
        $generator = new VapidKeyGenerator(
            pkeyNew: static fn (array $options) => false,
            libraryGenerate: static function (): array {
                throw new \RuntimeException('Unable to create the key');
            },
        );

        try {
            $generator->generate();
            $this->fail('Expected VapidGenerationException.');
        } catch (VapidGenerationException $e) {
            $this->assertCount(3, $e->diagnostics);
            $this->assertStringContainsString('Unable to create the key', $e->diagnosticReport());
        }
    }

    private function extractEnvValue(string $contents, string $key): string
    {
        preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches);

        return trim($matches[1] ?? '');
    }
}
