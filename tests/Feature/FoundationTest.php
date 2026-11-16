<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    // The homepage now reads the database (packages, services, ...), so the
    // schema must exist for these tests to run.
    use RefreshDatabase;
    public function test_public_homepage_renders_with_brand_identity(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('WP CCTV Indonesia')
            ->assertSee('Solusi CCTV untuk Keamanan Rumah dan Bisnis')
            ->assertSee('<meta property="og:type" content="website">', false);
    }

    public function test_login_page_is_available_with_authentication_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk admin')
            ->assertSee('<form', false);
    }

    public function test_admin_shell_requires_authenticated_user(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_framework_health_route_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_mariadb_connection_and_local_timezone_are_configured(): void
    {
        $this->assertSame('mariadb', config('database.connections.mariadb.driver'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }
}
