<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppClick extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_clicks';

    /**
     * Foreign key column name on related models.
     * Eloquent would auto-generate 'whats_app_click_id' from the model name,
     * but the actual column is 'whatsapp_click_id'.
     */
    public static function getForeignKeyName(): string
    {
        return 'whatsapp_click_id';
    }

    public const PAGE_TYPES = [
        'product', 'package', 'service', 'article', 'page', 'home',
    ];

    protected $fillable = [
        'event_id', 'page_url', 'page_title', 'page_type', 'context_id',
        'referrer', 'utm_source', 'utm_medium', 'utm_campaign',
        'device_category', 'visitor_token', 'converted_lead_id',
    ];

    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'whatsapp_click_id');
    }

    public function convertedLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'converted_lead_id');
    }

    public function scopeFromType($query, string $pageType)
    {
        return $query->where('page_type', $pageType);
    }

    public function scopeForContext($query, ?int $contextId, ?string $pageType = null)
    {
        if ($contextId !== null) {
            $query->where('context_id', $contextId);
        }
        if ($pageType !== null) {
            $query->where('page_type', $pageType);
        }

        return $query;
    }
}
