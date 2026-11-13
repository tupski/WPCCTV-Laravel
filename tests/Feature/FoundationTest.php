<?php

namespace Tests\Feature;

use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_public_foundation_page_renders_without_business_claims(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('WP CCTV Indonesia')
            ->assertSee('Informasi bisnis akan ditambahkan setelah materi terverifikasi.')
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
