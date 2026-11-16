<?php

namespace Tests\Unit;

use App\Services\ActivityLogService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Audit M3: `isSensitiveKey()` over-redacted (`author`, `author_name`) and
 * under-redacted (`passwd`).
 *
 * The three cases the audit named are all covered here, in both directions, as
 * is the ordering trap: `auth` must not catch `author`, but must catch `auth`,
 * `auth_key` and `x-auth`.
 */
class RedactionKeyRuleTest extends TestCase
{
    protected ActivityLogService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ActivityLogService();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sensitiveKeys(): array
    {
        return [
            'plain password' => ['password'],
            'password confirmation' => ['password_confirmation'],
            'legacy passwd' => ['passwd'],
            'passwd with prefix' => ['db_passwd'],
            'token' => ['token'],
            'api token' => ['api_token'],
            'access token camel' => ['accessToken'],
            'secret' => ['client_secret'],
            'key' => ['key'],
            'api key snake' => ['api_key'],
            'api key hyphen' => ['api-key'],
            'api key camel' => ['apiKey'],
            'private key' => ['private_key'],
            'p256dh' => ['p256dh'],
            'auth' => ['auth'],
            'auth_key' => ['auth_key'],
            'x-auth header' => ['x-auth'],
            'p256dh_auth' => ['p256dh_auth'],
            'prefix then auth' => ['x_auth'],
            'author_token still wins' => ['author_token'],
            'author auth_key' => ['author_auth_key'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function benignKeys(): array
    {
        return [
            'author' => ['author'],
            'author_name' => ['author_name'],
            'authority' => ['authority'],
            'authored_at' => ['authored_at'],
            'author id' => ['author_id'],
            'camel author' => ['authorName'],
            'capitalised' => ['Author'],
        ];
    }

    #[DataProvider('sensitiveKeys')]
    public function test_credential_keys_are_redacted(string $key): void
    {
        $this->assertTrue(
            $this->service->isSensitiveKey($key),
            "[{$key}] must be treated as sensitive."
        );
    }

    #[DataProvider('benignKeys')]
    public function test_benign_author_keys_are_not_redacted(string $key): void
    {
        $this->assertFalse(
            $this->service->isSensitiveKey($key),
            "[{$key}] must NOT be redacted; it is not a credential."
        );
    }

    /**
     * The documented list and the matcher must not drift.
     */
    public function test_redacted_key_patterns_are_the_documented_list(): void
    {
        $this->assertSame(
            ['password', 'passwd', 'token', 'secret', 'key', 'p256dh', 'auth'],
            ActivityLogService::REDACTED_KEY_PATTERNS
        );
    }

    /**
     * An empty key is not sensitive, and must not emit a warning from preg.
     */
    public function test_empty_key_is_benign(): void
    {
        $this->assertFalse($this->service->isSensitiveKey(''));
        $this->assertFalse($this->service->isSensitiveKey('   '));
    }
}
