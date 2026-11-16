<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDaily extends Model
{
    use HasFactory;

    protected $table = 'analytics_daily';

    protected $fillable = [
        'date', 'page_views', 'unique_sessions', 'whatsapp_clicks',
        'leads_created', 'leads_qualified', 'leads_won',
        'top_product_id', 'top_package_id', 'top_article_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'page_views' => 'integer',
            'unique_sessions' => 'integer',
            'whatsapp_clicks' => 'integer',
            'leads_created' => 'integer',
            'leads_qualified' => 'integer',
            'leads_won' => 'integer',
        ];
    }

    public function topProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'top_product_id');
    }

    public function topPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'top_package_id');
    }

    public function topArticle(): BelongsTo
    {
        return $this->belongsTo(BlogArticle::class, 'top_article_id');
    }
}
