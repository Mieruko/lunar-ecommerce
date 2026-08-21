<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingZone;
use App\Models\VietnamProvince;
use App\Models\VietnamWard;
use RuntimeException;

class ShippingFeeService
{
    public function quote(Cart $cart, string $provinceCode, string $wardCode): array
    {
        $cart->loadMissing('items');

        $subtotal = (int) $cart->items->sum(
            fn ($item) => (int) $item->unit_price_amount * (int) $item->quantity
        );

        $province = VietnamProvince::query()
            ->with('shippingZone')
            ->whereKey($provinceCode)
            ->first();

        if (! $province) {
            throw new RuntimeException('Tỉnh/Thành không hợp lệ.');
        }

        $ward = VietnamWard::query()
            ->with('shippingZone')
            ->whereKey($wardCode)
            ->where('province_code', $provinceCode)
            ->first();

        if (! $ward) {
            throw new RuntimeException('Phường/Xã không hợp lệ.');
        }

        /*
         * Phường/Xã có thể override khu vực của Tỉnh/Thành.
         * Hữu ích cho đảo, vùng xa hoặc khu vực shop muốn tính phí riêng.
         */
        $zone = $ward->shippingZone
            ?? $province->shippingZone
            ?? ShippingZone::query()
                ->where('code', 'standard')
                ->where('is_active', true)
                ->first();

        if (! $zone || ! $zone->is_active) {
            throw new RuntimeException('Khu vực giao hàng chưa được cấu hình.');
        }

        $threshold = (int) $zone->free_shipping_threshold_vnd;
        $freeShipping = $threshold > 0 && $subtotal >= $threshold;
        $fee = $freeShipping ? 0 : (int) $zone->fee_vnd;

        return [
            'zone_id' => $zone->id,
            'zone_code' => $zone->code,
            'zone_name' => $zone->name,
            'shipping_fee' => $fee,
            'base_fee' => (int) $zone->fee_vnd,
            'free_shipping' => $freeShipping,
            'free_shipping_threshold' => $threshold,
            'subtotal' => $subtotal,
            'total' => $subtotal + $fee,
        ];
    }
}
