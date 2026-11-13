<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'seoable_type', 'seoable_id',
        'seo_title', 'meta_description', 'canonical', 'robots',
        'og_title', 'og_description', 'og_image', 'og_type',
        'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
