<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function watchDetail(): HasOne
    {
        return $this->hasOne(WatchDetail::class);
    }

    public function jewelryDetail(): HasOne
    {
        return $this->hasOne(JewelryDetail::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'product_materials')
            ->withPivot(['percentage', 'notes']);
    }

    public function gemstones(): BelongsToMany
    {
        return $this->belongsToMany(Gemstone::class, 'product_gemstones')
            ->withPivot(['product_variant_id', 'quantity', 'total_carat', 'cut_grade', 'color_grade', 'clarity_grade', 'setting_type']);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
