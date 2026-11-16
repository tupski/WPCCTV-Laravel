<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsPageView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'analytics_page_views';

    protected $fillable = [
        'session_id', 'page_url', 'page_type', 'ref_domain',
        'utm_source', 'utm_medium', 'utm_campaign',
        'device_category', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function scopeFromType($query, string $pageType)
    {
        return $query->where('page_type', $pageType);
    }

    public function scopeSince($query, $from)
    {
        return $query->where('created_at', '>=', $from);
    }
}
