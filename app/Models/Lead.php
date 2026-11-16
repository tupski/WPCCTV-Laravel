<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_QUOTATION = 'quotation';
    public const STATUS_NEGOTIATION = 'negotiation';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW, self::STATUS_CONTACTED, self::STATUS_QUALIFIED,
        self::STATUS_QUOTATION, self::STATUS_NEGOTIATION,
        self::STATUS_WON, self::STATUS_LOST, self::STATUS_ARCHIVED,
    ];

    public const TYPE_INSTALLATION = 'installation';
    public const TYPE_REPAIR = 'repair';
    public const TYPE_CONSULTATION = 'consultation';

    public const TYPES = [
        self::TYPE_INSTALLATION, self::TYPE_REPAIR, self::TYPE_CONSULTATION,
    ];

    protected $fillable = [
        'whatsapp_click_id', 'customer_name', 'whatsapp_number', 'lead_type',
        'requirements', 'status', 'priority', 'notes', 'follow_up_at',
        'assigned_to', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function whatsappClick(): BelongsTo
    {
        return $this->belongsTo(WhatsAppClick::class, 'whatsapp_click_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_NEW, self::STATUS_CONTACTED, self::STATUS_QUALIFIED,
            self::STATUS_QUOTATION, self::STATUS_NEGOTIATION,
        ]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_WON, self::STATUS_LOST, self::STATUS_ARCHIVED]);
    }
}
