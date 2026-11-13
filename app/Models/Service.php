<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Service extends Model
{
    use HasFactory, HasSlugs;

    protected $fillable = [
        'type', 'title', 'slug', 'description',
        'scope', 'applications', 'process', 'published',
    ];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'applications' => 'array',
            'process' => 'array',
            'published' => 'boolean',
        ];
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

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getSlugSource(): string
    {
        return $this->title ?? '';
    }
}
