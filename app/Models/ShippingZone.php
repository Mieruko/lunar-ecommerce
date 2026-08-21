<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fee_vnd' => 'integer',
            'free_shipping_threshold_vnd' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(VietnamProvince::class, 'shipping_zone_id');
    }

    public function wards(): HasMany
    {
        return $this->hasMany(VietnamWard::class, 'shipping_zone_id');
    }
}
