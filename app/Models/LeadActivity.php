<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory;

    public const ACTION_STATUS_CHANGE = 'status_change';
    public const ACTION_NOTE = 'note';
    public const ACTION_ASSIGN = 'assign';
    public const ACTION_FOLLOWUP = 'followup';
    public const ACTION_IDENTIFY = 'identify';
    public const ACTION_CONVERT = 'convert';
    public const ACTION_WHATSAPP_CLICK = 'whatsapp_click';

    public const ACTIONS = [
        self::ACTION_STATUS_CHANGE, self::ACTION_NOTE, self::ACTION_ASSIGN,
        self::ACTION_FOLLOWUP, self::ACTION_IDENTIFY, self::ACTION_CONVERT,
        self::ACTION_WHATSAPP_CLICK,
    ];

    public $timestamps = false;

    protected $fillable = [
        'lead_id', 'user_id', 'action', 'field',
        'old_value', 'new_value', 'whatsapp_click_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappClick(): BelongsTo
    {
        return $this->belongsTo(WhatsAppClick::class, 'whatsapp_click_id');
    }

    /**
     * Newest first, with `id` as a deterministic tiebreaker.
     *
     * `lead_activities.created_at` is a plain timestamp, so two activities
     * recorded in the same second compare equal. Ordering by `created_at`
     * alone left the timeline order up to the query plan (the same shape of
     * nondeterminism that made WhatsAppTrackingTest flaky). `id` is monotonic
     * for inserts, so it resolves every tie in true insertion order.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
