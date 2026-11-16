<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected $keyType = 'int';

    protected $fillable = [
        // `user_id` is fillable only so forUser() can insert the row; controllers
        // always force it from the authenticated session, never from input.
        'user_id', 'whatsapp_clicks_enabled', 'lead_updates_enabled',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_clicks_enabled' => 'boolean',
            'lead_updates_enabled' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Column defaults, in one place so the model and the schema cannot drift.
     *
     * @var array<string, bool>
     */
    public const DEFAULTS = [
        'whatsapp_clicks_enabled' => true,
        'lead_updates_enabled' => true,
    ];

    /**
     * Get the notification preference for a user.
     *
     * Returns a default-enabled, UNSAVED model when no row exists. It is
     * deliberately not a read-modify-write: callers that render a form get the
     * same values, and callers that persist explicitly still call save().
     *
     * The default-enabled contract here is what fixes audit finding H1: the
     * push pipeline must treat "admin never opened the preferences screen" as
     * "wants notifications", not as "disabled".
     */
    public static function forUser(int $userId): static
    {
        return static::query()->whereKey($userId)->first()
            ?? new static([
                'user_id' => $userId,
                ...self::DEFAULTS,
            ]);
    }

    /**
     * Get the persisted preferences for a user, creating the default row if
     * missing. Used by the write paths (controllers) only.
     */
    public static function firstOrCreateForUser(int $userId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                ...self::DEFAULTS,
                'updated_at' => now(),
            ],
        );
    }

    /**
     * Determine whether a user should receive WhatsApp click pushes.
     *
     * Absence of a row means enabled (see H1). Only an explicit stored `false`
     * opts a user out.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    public static function whatsappClicksOptOuts(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return static::query()
            ->whereIn('user_id', $userIds)
            ->where('whatsapp_clicks_enabled', false)
            ->pluck('user_id')
            ->all();
    }
}
