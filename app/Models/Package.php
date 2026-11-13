<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Package extends Model
{
    use HasFactory, HasSlugs;

    protected $fillable = [
        'brand_id', 'title', 'slug', 'description', 'hero_image',
        'camera_quantity', 'included_equipment', 'installation_scope',
        'relevant_specs', 'installation_process', 'exclusions',
        'price', 'published',
    ];

    protected function casts(): array
    {
        return [
            'included_equipment' => 'array',
            'installation_scope' => 'array',
            'relevant_specs' => 'array',
            'installation_process' => 'array',
            'exclusions' => 'array',
            'price' => 'decimal:2',
            'published' => 'boolean',
            'camera_quantity' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(PackageBrand::class, 'brand_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PackageCategory::class, 'package_category_package');
    }

    public function usageCategories(): BelongsToMany
    {
        return $this->belongsToMany(PackageUsageCategory::class, 'package_usage_category_package');
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
        return $this->title ?? '';
    }
}
