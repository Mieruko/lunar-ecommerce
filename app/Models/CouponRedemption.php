<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = [];
    protected function casts(): array { return ['redeemed_at' => 'datetime']; }
    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
