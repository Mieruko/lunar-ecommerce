<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CartItem extends Model { protected $guarded = []; public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); } }
