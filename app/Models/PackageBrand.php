<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageBrand extends Model
{
    use HasFactory, HasSlugs;

    protected $fillable = ['name', 'slug', 'logo', 'description'];

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'brand_id');
    }

    public function getSlugSource(): string
    {
        return $this->name ?? '';
    }
}
