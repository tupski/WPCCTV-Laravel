<?php

namespace App\Models;

use Illuminate\Support\Str;

trait HasSlugs
{
    /**
     * The attribute used to generate the slug (default: title or name).
     */
    protected function getSlugSource(): string
    {
        return $this->title ?? $this->name ?? '';
    }

    public static function bootHasSlugs(): void
    {
        static::creating(function ($model) {
            $slugSource = $model->getSlugSource();

            if (empty($model->slug)) {
                $model->slug = $model->generateUniqueSlug($slugSource);
            }
        });

        static::updating(function ($model) {
            // Only auto-generate on update if slug was not manually set
            if (empty($model->slug) && !$model->isDirty('slug')) {
                $slugSource = $model->getSlugSource();
                $model->slug = $model->generateUniqueSlug($slugSource, $model->id);
            }
        });
    }

    protected function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $i = 2;

        while ($this->modelClass()::where($this->getSlugColumn(), $slug)
            ->when($ignoreId, fn ($q, $id) => $q->where($this->getPrimaryKey(), '!=', $id))
            ->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    protected function modelClass(): string
    {
        return static::class;
    }

    protected function getSlugColumn(): string
    {
        return 'slug';
    }
}
