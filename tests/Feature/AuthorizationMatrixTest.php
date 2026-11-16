<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Asserts the full 5-role × 7-gate authorization matrix.
 *
 * Gate definitions (AppServiceProvider):
 *   admin            → super_admin, administrator
 *   manage-content   → super_admin, administrator, content_editor
 *   manage-leads     → super_admin, administrator, lead_manager
 *   view-analytics   → super_admin, administrator, analyst
 *   manage-settings  → super_admin, administrator
 *   manage-users     → super_admin
 *   view-activity-logs → super_admin, administrator, lead_manager, analyst
 */
class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Expected access matrix.
     *
     * key = role, value = map of gate => true (allowed) / false (denied)
     */
    private static function expectedMatrix(): array
    {
        return [
            User::ROLE_SUPER_ADMIN => [
                'admin' => true,
                'manage-content' => true,
                'manage-leads' => true,
                'view-analytics' => true,
                'manage-settings' => true,
                'manage-users' => true,
                'view-activity-logs' => true,
            ],
            User::ROLE_ADMINISTRATOR => [
                'admin' => true,
                'manage-content' => true,
                'manage-leads' => true,
                'view-analytics' => true,
                'manage-settings' => true,
                'manage-users' => false,
                'view-activity-logs' => true,
            ],
            User::ROLE_CONTENT_EDITOR => [
                'admin' => false,
                'manage-content' => true,
                'manage-leads' => false,
                'view-analytics' => false,
                'manage-settings' => false,
                'manage-users' => false,
                'view-activity-logs' => false,
            ],
            User::ROLE_LEAD_MANAGER => [
                'admin' => false,
                'manage-content' => false,
                'manage-leads' => true,
                'view-analytics' => false,
                'manage-settings' => false,
                'manage-users' => false,
                'view-activity-logs' => true,
            ],
            User::ROLE_ANALYST => [
                'admin' => false,
                'manage-content' => false,
                'manage-leads' => false,
                'view-analytics' => true,
                'manage-settings' => false,
                'manage-users' => false,
                'view-activity-logs' => true,
            ],
        ];
    }

    #[DataProvider('roleProvider')]
    public function test_gate_matrix(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $expected = $this->expectedMatrix()[$role];

        $this->actingAs($user);

        foreach ($expected as $gate => $allowed) {
            if ($allowed) {
                $this->assertTrue(
                    $user->can($gate),
                    "Role [{$role}] should allow gate [{$gate}].",
                );
            } else {
                $this->assertFalse(
                    $user->can($gate),
                    "Role [{$role}] should deny gate [{$gate}].",
                );
            }
        }
    }

    public static function roleProvider(): array
    {
        $matrix = self::expectedMatrix();

        $cases = [];
        foreach (array_keys($matrix) as $role) {
            $cases[$role] = [$role];
        }

        return $cases;
    }

    public function test_user_has_role_returns_true_for_matching_role(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->hasRole(User::ROLE_SUPER_ADMIN));
        $this->assertFalse($user->hasRole(User::ROLE_CONTENT_EDITOR));
        $this->assertTrue($user->hasRole(User::ROLE_ANALYST, User::ROLE_SUPER_ADMIN));
    }

    public function test_user_is_admin_true_for_admin_roles(): void
    {
        $super = User::factory()->superAdmin()->create();
        $admin = User::factory()->administrator()->create();
        $editor = User::factory()->contentEditor()->create();

        $this->assertTrue($super->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($editor->isAdmin());
    }

    public function test_admin_gate_uses_admin_roles(): void
    {
        $super = User::factory()->superAdmin()->create();
        $admin = User::factory()->administrator()->create();
        $lead = User::factory()->leadManager()->create();

        $this->assertTrue($super->can('admin'));
        $this->assertTrue($admin->can('admin'));
        $this->assertFalse($lead->can('admin'));
    }

    public function test_scope_admin_filters_only_admin_roles(): void
    {
        User::factory()->superAdmin()->create();
        User::factory()->administrator()->create();
        User::factory()->contentEditor()->create();
        User::factory()->leadManager()->create();
        User::factory()->analyst()->create();

        $this->assertSame(2, User::admin()->count());
    }

    public function test_create_admin_command_role_passes_admin_gate(): void
    {
        // CreateAdminCommand creates a user without explicit role.
        // The DB default is 'administrator' after data migration converts 'admin'.
        // Simulate the bootstrap user as the migration data step would leave it.
        $user = User::factory()->administrator()->create();

        $this->assertTrue($user->can('admin'));
        $this->assertTrue($user->isAdmin());
    }
}
