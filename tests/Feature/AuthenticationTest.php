<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_authenticates_and_returns_to_intended_path(): void
    {
        $user = User::factory()->create(['password' => 'ValidPassword123!']);

        $this->withSession(['url.intended' => '/admin'])
            ->post('/login', ['email' => $user->email, 'password' => 'ValidPassword123!'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_is_rejected_and_throttled_after_five_attempts(): void
    {
        $emailKey = 'login:email:'.hash('sha256', 'missing@example.com');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => 'missing@example.com',
                'password' => 'incorrect',
            ])->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
        }

        $this->from('/login')->post('/login', [
            'email' => 'missing@example.com',
            'password' => 'incorrect',
        ])->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);

        $this->assertSame(5, RateLimiter::attempts($emailKey));
    }

    public function test_account_throttle_is_shared_across_ips_and_email_is_case_insensitive(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$attempt])
                ->post('/login', ['email' => 'Missing@Example.com', 'password' => 'incorrect']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->from('/login')->post('/login', ['email' => 'missing@example.com', 'password' => 'incorrect'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);

        $this->assertSame(5, RateLimiter::attempts('login:email:'.hash('sha256', 'missing@example.com')));
    }

    public function test_ip_spray_throttle_is_shared_across_emails_and_ignores_untrusted_forwarded_for(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.9',
                'HTTP_X_FORWARDED_FOR' => '198.51.100.'.$attempt,
            ])->post('/login', ['email' => "user{$attempt}@example.com", 'password' => 'incorrect']);
        }

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_X_FORWARDED_FOR' => '192.0.2.88',
        ])->from('/login')->post('/login', ['email' => 'another@example.com', 'password' => 'incorrect'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);

        $ipKey = 'login:ip:'.hash('sha256', '203.0.113.9');
        $this->assertSame(30, RateLimiter::attempts($ipKey));
    }

    public function test_guest_is_redirected_to_login_and_authenticated_user_can_open_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');

        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_logout_invalidates_session_and_regenerates_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->withSession(['private.session.value' => 'present']);

        $oldToken = session()->token();

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertNotSame($oldToken, session()->token());
        $this->assertNull(session()->get('private.session.value'));
    }

    public function test_no_public_registration_route_exists(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertSame(404, $this->get('/register')->getStatusCode());
    }

    public function test_login_page_has_post_form_and_csrf_field(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_admin_bootstrap_creates_only_one_account_and_never_prints_secrets(): void
    {
        putenv('ADMIN_BOOTSTRAP_NAME=Initial Operator');
        putenv('ADMIN_BOOTSTRAP_EMAIL=operator@example.com');
        putenv('ADMIN_BOOTSTRAP_PASSWORD=BootstrapPassword123!');

        $this->artisan('admin:create')->assertExitCode(0)->doesntExpectOutput('BootstrapPassword123!');

        $user = User::query()->sole();
        $this->assertSame('operator@example.com', $user->email);
        $this->assertTrue(Hash::check('BootstrapPassword123!', $user->password));

        $this->artisan('admin:create')->assertExitCode(1)->doesntExpectOutput('BootstrapPassword123!');
        $this->assertSame(1, User::query()->count());
    }

    public function test_admin_bootstrap_refuses_missing_credentials_without_leaking_password(): void
    {
        putenv('ADMIN_BOOTSTRAP_NAME');
        putenv('ADMIN_BOOTSTRAP_EMAIL');
        putenv('ADMIN_BOOTSTRAP_PASSWORD');

        $this->artisan('admin:create')
            ->assertExitCode(1)
            ->doesntExpectOutput('BootstrapPassword123!')
            ->expectsOutputToContain('Bootstrap credentials missing or invalid.');

        $this->assertSame(0, User::query()->count());
    }
}
