<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'context_type', 'context_id', 'question', 'answer',
        'sort_order', 'published',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'published' => 'boolean',
        ];
    }

    public function context(): MorphTo
    {
        return $this->morphTo('context', 'context_type', 'context_id');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function scopeForContext($query, Model $parent)
    {
        return $query
            ->where('context_type', $parent->getMorphClass())
            ->where('context_id', $parent->getKey())
            ->orderBy('sort_order');
    }
}
