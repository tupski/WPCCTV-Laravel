<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBrand extends Model
{
    use HasFactory, HasSlugs;

    protected $fillable = ['name', 'slug', 'logo', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function scopePublished($query)
    {
        return $query->with('products')->get()->filter(fn (ProductBrand $brand) => $brand->products->isNotEmpty());
    }
}
