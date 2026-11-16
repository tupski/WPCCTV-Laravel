<?php

namespace App\Models;

use Illuminate\Database\UniqueConstraintViolationException;
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
            if (empty($model->slug) && ! $model->isDirty('slug')) {
                $slugSource = $model->getSlugSource();
                $model->slug = $model->generateUniqueSlug($slugSource, $model->id);
            }
        });
    }

    /**
     * Save the model, retrying with a new slug candidate when the insert hits
     * the slug unique index (concurrent-create race: both requests pass the
     * exists() check, only one insert can win).
     *
     * The DB unique index stays the source of truth; the exists()-loop handles
     * the common path and this retry covers the race window. Non-slug unique
     * violations and retry-budget exhaustion rethrow — no silent corruption.
     */
    public function save(array $options = []): bool
    {
        try {
            return parent::save($options);
        } catch (UniqueConstraintViolationException $exception) {
            if (empty($this->slug) || ! $this->isSlugColumnViolation($exception)) {
                throw $exception;
            }

            return $this->retrySaveWithNewSlug($exception);
        }
    }

    protected function isSlugColumnViolation(UniqueConstraintViolationException $exception): bool
    {
        return str_contains($exception->getMessage(), $this->getSlugColumn());
    }

    protected function retrySaveWithNewSlug(UniqueConstraintViolationException $exception): bool
    {
        // Re-throw the original failure if the retry budget is exhausted.
        $attempts = 0;

        while ($attempts < 10) {
            $attempts++;

            $this->slug = $this->nextSlugCandidate(
                Str::slug($this->getSlugSource()) ?: 'item',
                $attempts,
            );

            try {
                $this->exists = false;
                $this->wasRecentlyCreated = false;
                $this->syncOriginal();

                return parent::save();
            } catch (UniqueConstraintViolationException $e) {
                continue;
            }
        }

        throw $exception;
    }

    /**
     * Next slug candidate after a unique violation: sequential "-2", "-3", ...
     * first, then a random suffix so concurrent creation always converges.
     */
    protected function nextSlugCandidate(string $base, int $attempt): string
    {
        if ($attempt <= 5) {
            return $base.'-'.($attempt + 1);
        }

        return $base.'-'.Str::lower(Str::random(6));
    }

    protected function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $i = 2;

        while ($this->modelClass()::where($this->getSlugColumn(), $slug)
            ->when($ignoreId, fn ($q, $id) => $q->where($this->getPrimaryKey(), '!=', $id))
            ->exists()) {
            $slug = $base.'-'.$i++;
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
