<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ProductVariant extends Model {
    protected $guarded = [];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function inventory(): HasMany { return $this->hasMany(Inventory::class); }
    public function availableStock(): int { return max(0, (int) $this->inventory()->sum('quantity_on_hand') - (int) $this->inventory()->sum('quantity_reserved')); }
}
