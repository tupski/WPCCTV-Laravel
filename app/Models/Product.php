<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Product extends Model
{
    use HasFactory, HasSlugs;

    protected $fillable = [
        'brand_id', 'name', 'slug', 'model', 'camera_type', 'description',
        'key_features', 'technical_specs', 'recommended_applications',
        'image', 'gallery', 'published',
    ];

    protected function casts(): array
    {
        return [
            'key_features' => 'array',
            'technical_specs' => 'array',
            'recommended_applications' => 'array',
            'gallery' => 'array',
            'published' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'context');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function getSlugSource(): string
    {
        return $this->name ?? '';
    }
}
