<?php

namespace App\Services;

use App\Models\VietnamProvince;
use App\Models\VietnamWard;
use Illuminate\Support\Collection;
use RuntimeException;

class VietnamAddressService
{
    public function provinces(): Collection
    {
        return VietnamProvince::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name', 'full_name']);
    }

    public function wards(string $provinceCode): Collection
    {
        return VietnamWard::query()
            ->where('province_code', $provinceCode)
            ->orderBy('name')
            ->get(['code', 'name', 'full_name', 'province_code']);
    }

    public function resolve(string $provinceCode, string $wardCode): array
    {
        $province = VietnamProvince::query()
            ->whereKey($provinceCode)
            ->first();

        if (! $province) {
            throw new RuntimeException('Tỉnh/Thành đã chọn không hợp lệ.');
        }

        $ward = VietnamWard::query()
            ->whereKey($wardCode)
            ->where('province_code', $provinceCode)
            ->first();

        if (! $ward) {
            throw new RuntimeException('Phường/Xã đã chọn không thuộc Tỉnh/Thành này.');
        }

        return [
            'province' => $province,
            'ward' => $ward,
        ];
    }
}
