<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PackageUsageCategory extends Model
{
    use HasFactory, HasSlugs;

    protected $fillable = ['name', 'slug'];

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_usage_category_package');
    }

    public function getSlugSource(): string
    {
        return $this->name ?? '';
    }
}
