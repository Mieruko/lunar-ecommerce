<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['placed_at' => 'datetime'];
    }

    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function shipments(): HasMany { return $this->hasMany(Shipment::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function addresses(): HasMany { return $this->hasMany(OrderAddress::class); }
    public function shippingAddress(): HasOne { return $this->hasOne(OrderAddress::class)->where('address_type', 'shipping'); }
    public function billingAddress(): HasOne { return $this->hasOne(OrderAddress::class)->where('address_type', 'billing'); }
    public function statusHistory(): HasMany { return $this->hasMany(OrderStatusHistory::class)->latest(); }
    public function notes(): HasMany { return $this->hasMany(OrderNote::class)->latest(); }
    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function couponRedemption(): HasOne { return $this->hasOne(CouponRedemption::class); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class); }
    public function returnRequests(): HasMany { return $this->hasMany(ReturnRequest::class); }
}
