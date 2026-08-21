<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    protected $guarded = [];

    public function wishlist(): BelongsTo { return $this->belongsTo(Wishlist::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
