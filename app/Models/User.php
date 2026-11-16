<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_CONTENT_EDITOR = 'content_editor';
    public const ROLE_LEAD_MANAGER = 'lead_manager';
    public const ROLE_ANALYST = 'analyst';

    /**
     * All roles recognized by the system.
     */
    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMINISTRATOR,
        self::ROLE_CONTENT_EDITOR,
        self::ROLE_LEAD_MANAGER,
        self::ROLE_ANALYST,
    ];

    /**
     * Roles considered "admin" for the legacy admin gate.
     * Includes the legacy 'admin' string value so Phase 3 tests stay green.
     */
    public const ADMIN_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMINISTRATOR,
        'admin', // legacy value, kept for backward compatibility
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Scope: filter users whose role is one of the given roles.
     */
    public function scopeAdmin($query)
    {
        return $query->whereIn('role', self::ADMIN_ROLES);
    }

    /**
     * The user's active push subscriptions.
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Check whether the user has any of the given roles.
     *
     * @param string ...$roles
     */
    public function hasRole(string ...$roles): bool
    {
        if (empty($roles)) {
            return false;
        }

        return in_array($this->role, $roles, true);
    }

    /**
     * Legacy alias: true when the user is super_admin or administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(...self::ADMIN_ROLES);
    }
}
