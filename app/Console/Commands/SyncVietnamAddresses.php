<?php

namespace App\Console\Commands;

use App\Models\ShippingZone;
use App\Models\VietnamProvince;
use App\Models\VietnamWard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncVietnamAddresses extends Command
{
    protected $signature = 'lunar:sync-vietnam-addresses
                            {--file= : Đọc JSON từ file local thay vì tải từ GitHub}
                            {--url= : Ghi đè URL dữ liệu mặc định}';

    protected $description = 'Import 34 Tỉnh/Thành và 3.321 Phường/Xã/Đặc khu vào MySQL';

    private const DEFAULT_URL =
        'https://raw.githubusercontent.com/thanglequoc/vietnamese-provinces-database/master/json/vn_only_simplified_json_generated_data_vn_units_2026-07-25__20_49_07.json';

    public function handle(): int
    {
        try {
            $payload = $this->loadPayload();
            $this->validatePayload($payload);

            DB::transaction(function () use ($payload) {
                $standardZone = $this->seedShippingZones();

                $provinceCodes = [];
                $wardCodes = [];

                foreach ($payload as $provinceIndex => $provinceData) {
                    $provinceCode = str_pad((string) $provinceData['Code'], 2, '0', STR_PAD_LEFT);
                    $provinceCodes[] = $provinceCode;

                    $fullProvinceName = trim((string) $provinceData['FullName']);

                    VietnamProvince::query()->updateOrCreate(
                        ['code' => $provinceCode],
                        [
                            'name' => $this->stripAdministrativePrefix($fullProvinceName),
                            'full_name' => $fullProvinceName,
                            'shipping_zone_id' => VietnamProvince::query()
                                ->whereKey($provinceCode)
                                ->value('shipping_zone_id') ?: $standardZone->id,
                            'sort_order' => $provinceIndex + 1,
                        ]
                    );

                    foreach ($provinceData['Wards'] as $wardData) {
                        $wardCode = str_pad((string) $wardData['Code'], 5, '0', STR_PAD_LEFT);
                        $wardCodes[] = $wardCode;

                        $fullWardName = trim((string) $wardData['FullName']);

                        VietnamWard::query()->updateOrCreate(
                            ['code' => $wardCode],
                            [
                                'province_code' => $provinceCode,
                                'name' => $this->stripAdministrativePrefix($fullWardName),
                                'full_name' => $fullWardName,
                                'unit_type' => $this->unitType($fullWardName),
                                // Không ghi đè shipping_zone_id để admin có thể cấu hình override.
                            ]
                        );
                    }
                }

                VietnamWard::query()
                    ->whereNotIn('code', $wardCodes)
                    ->delete();

                VietnamProvince::query()
                    ->whereNotIn('code', $provinceCodes)
                    ->delete();
            });

            $this->newLine();
            $this->info('Đã đồng bộ dữ liệu hành chính Việt Nam thành công.');
            $this->line('Tỉnh/Thành: '.VietnamProvince::query()->count());
            $this->line('Phường/Xã/Đặc khu: '.VietnamWard::query()->count());
            $this->line('Khu vực ship: '.ShippingZone::query()->count());

            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function loadPayload(): array
    {
        if ($file = $this->option('file')) {
            if (! is_file($file)) {
                throw new RuntimeException("Không tìm thấy file: {$file}");
            }

            $json = file_get_contents($file);
        } else {
            $url = (string) ($this->option('url') ?: self::DEFAULT_URL);

            $this->line('Đang tải dữ liệu hành chính một lần từ:');
            $this->line($url);

            $response = Http::acceptJson()
                ->connectTimeout(8)
                ->timeout(30)
                ->retry(2, 500)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Không tải được dữ liệu. Bạn có thể tải JSON bằng trình duyệt rồi chạy: '.
                    'php artisan lunar:sync-vietnam-addresses --file="C:\\duong-dan\\vietnam_units.json"'
                );
            }

            $json = $response->body();
        }

        $payload = json_decode($json, true);

        if (! is_array($payload)) {
            throw new RuntimeException('File JSON không đúng định dạng.');
        }

        return $payload;
    }

    private function validatePayload(array $payload): void
    {
        if (count($payload) !== 34) {
            throw new RuntimeException(
                'Từ chối import: dữ liệu phải có đúng 34 Tỉnh/Thành, hiện nhận được '.count($payload).'.'
            );
        }

        $wardCount = 0;

        foreach ($payload as $province) {
            if (
                ! isset($province['Code'], $province['FullName'], $province['Wards'])
                || ! is_array($province['Wards'])
            ) {
                throw new RuntimeException('Từ chối import: cấu trúc Tỉnh/Thành không hợp lệ.');
            }

            $wardCount += count($province['Wards']);
        }

        if ($wardCount !== 3321) {
            throw new RuntimeException(
                "Từ chối import: dữ liệu phải có đúng 3.321 đơn vị cấp xã, hiện nhận được {$wardCount}."
            );
        }
    }

    private function seedShippingZones(): ShippingZone
    {
        /*
         * Đây là giá mẫu nghiệp vụ, KHÔNG phải bảng giá của hãng vận chuyển.
         * Chỉ "standard" được gán mặc định. Các zone khác được tạo sẵn để
         * admin có thể phân tỉnh/xã theo chính sách thực tế của shop.
         */
        $zones = [
            ['code' => 'near', 'name' => 'Khu vực gần', 'fee_vnd' => 25_000],
            ['code' => 'standard', 'name' => 'Khu vực tiêu chuẩn', 'fee_vnd' => 30_000],
            ['code' => 'remote', 'name' => 'Khu vực xa', 'fee_vnd' => 40_000],
            ['code' => 'special', 'name' => 'Hải đảo / khu vực đặc biệt', 'fee_vnd' => 50_000],
        ];

        foreach ($zones as $zone) {
            ShippingZone::query()->updateOrCreate(
                ['code' => $zone['code']],
                [
                    'name' => $zone['name'],
                    'fee_vnd' => $zone['fee_vnd'],
                    'free_shipping_threshold_vnd' => 5_000_000,
                    'is_active' => true,
                ]
            );
        }

        return ShippingZone::query()->where('code', 'standard')->firstOrFail();
    }

    private function stripAdministrativePrefix(string $name): string
    {
        return preg_replace(
            '/^(Thành phố|Tỉnh|Phường|Xã|Đặc khu)\s+/u',
            '',
            $name
        ) ?: $name;
    }

    private function unitType(string $name): ?string
    {
        foreach (['Phường', 'Xã', 'Đặc khu'] as $type) {
            if (str_starts_with($name, $type.' ')) {
                return mb_strtolower($type);
            }
        }

        return null;
    }
}
