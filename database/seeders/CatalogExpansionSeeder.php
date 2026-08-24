<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Gemstone;
use App\Models\Inventory;
use App\Models\JewelryDetail;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WatchDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogExpansionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $watchRoot = Category::updateOrCreate(['slug' => 'watches'], [
            'name' => 'Đồng hồ', 'description' => 'Đồng hồ chính hãng với thông số minh bạch.', 'is_active' => true, 'sort_order' => 1,
        ]);
        $jewelryRoot = Category::updateOrCreate(['slug' => 'jewelry'], [
            'name' => 'Trang sức', 'description' => 'Trang sức tinh tế cho mọi khoảnh khắc.', 'is_active' => true, 'sort_order' => 2,
        ]);

        $categories = [
            'automatic-watches' => Category::updateOrCreate(['slug' => 'automatic-watches'], ['name' => 'Đồng hồ cơ', 'parent_id' => $watchRoot->id, 'is_active' => true, 'sort_order' => 1]),
            'dress-watches' => Category::updateOrCreate(['slug' => 'dress-watches'], ['name' => 'Đồng hồ thanh lịch', 'parent_id' => $watchRoot->id, 'is_active' => true, 'sort_order' => 2]),
            'sport-watches' => Category::updateOrCreate(['slug' => 'sport-watches'], ['name' => 'Đồng hồ thể thao', 'parent_id' => $watchRoot->id, 'is_active' => true, 'sort_order' => 3]),
            'rings' => Category::updateOrCreate(['slug' => 'rings'], ['name' => 'Nhẫn', 'parent_id' => $jewelryRoot->id, 'is_active' => true, 'sort_order' => 1]),
            'earrings' => Category::updateOrCreate(['slug' => 'earrings'], ['name' => 'Bông tai', 'parent_id' => $jewelryRoot->id, 'is_active' => true, 'sort_order' => 2]),
            'necklaces' => Category::updateOrCreate(['slug' => 'necklaces'], ['name' => 'Dây chuyền', 'parent_id' => $jewelryRoot->id, 'is_active' => true, 'sort_order' => 3]),
            'bracelets' => Category::updateOrCreate(['slug' => 'bracelets'], ['name' => 'Lắc tay', 'parent_id' => $jewelryRoot->id, 'is_active' => true, 'sort_order' => 4]),
            'pendants' => Category::updateOrCreate(['slug' => 'pendants'], ['name' => 'Mặt dây chuyền', 'parent_id' => $jewelryRoot->id, 'is_active' => true, 'sort_order' => 5]),
        ];

        $brands = collect([
            'lunar-atelier' => ['LUNAR Atelier', 'Thiết kế trang sức tuyển chọn của LUNAR JEWELS.', null],
            'seiko' => ['Seiko', 'Nhà chế tác đồng hồ Nhật Bản.', 'https://www.seikowatches.com/'],
            'citizen' => ['Citizen', 'Đồng hồ Nhật Bản với công nghệ Eco-Drive.', 'https://www.citizenwatch-global.com/'],
            'orient' => ['Orient', 'Đồng hồ cơ Nhật Bản.', 'https://www.orient-watch.com/'],
            'tissot' => ['Tissot', 'Đồng hồ Thụy Sĩ từ năm 1853.', 'https://www.tissotwatches.com/'],
            'casio' => ['Casio', 'Đồng hồ Nhật Bản bền bỉ và thực dụng.', 'https://www.casio.com/'],
        ])->mapWithKeys(fn (array $data, string $slug): array => [
            $slug => Brand::updateOrCreate(['slug' => $slug], [
                'name' => $data[0], 'description' => $data[1], 'website' => $data[2], 'is_active' => true,
            ]),
        ]);

        $records = $this->records();
        $warehouse = Warehouse::updateOrCreate(['code' => 'HCM-01'], [
            'name' => 'Kho chính TP. Hồ Chí Minh', 'province' => 'Hồ Chí Minh', 'country_code' => 'VN', 'is_active' => true,
        ]);

        foreach ($records as $index => $record) {
            $product = Product::updateOrCreate(['slug' => $record['slug']], [
                'brand_id' => $brands[$record['brand']]->id,
                'category_id' => $categories[$record['category']]->id,
                'name' => $record['name'],
                'product_type' => $record['type'],
                'short_description' => $record['short'],
                'description' => $record['description'],
                'status' => 'active',
                'base_price_amount' => $record['price'],
                'currency' => 'VND',
                'is_featured' => $index < 6,
                'seo_title' => $record['name'].' | LUNAR JEWELS',
                'seo_description' => $record['short'],
                'source_url' => $record['source'],
            ]);
            $variant = ProductVariant::updateOrCreate(['sku' => $record['sku']], [
                'product_id' => $product->id,
                'name' => $record['variant'],
                'price_amount' => $record['price'],
                'compare_at_price_amount' => $record['compare_at'] ?? null,
                'weight_grams' => $record['weight'] ?? null,
                'status' => 'active',
            ]);
            ProductImage::updateOrCreate(['product_id' => $product->id, 'is_primary' => true], [
                'product_variant_id' => $variant->id,
                'storage_disk' => 'external',
                'path' => $record['image'],
                'alt_text' => $record['name'],
                'sort_order' => 1,
                'is_licensed' => true,
                'source_url' => $record['image'],
            ]);
            Inventory::updateOrCreate(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id], [
                'quantity_on_hand' => $record['stock'], 'quantity_reserved' => 0, 'reorder_level' => 2,
            ]);

            if ($record['type'] === 'watch') {
                WatchDetail::updateOrCreate(['product_id' => $product->id], $record['detail']);

                continue;
            }

            JewelryDetail::updateOrCreate(['product_id' => $product->id], $record['detail']);
            $this->syncMaterials($product, $record['materials']);
            $this->syncGemstones($product, $variant, $record['gemstones'] ?? []);
        }
    }

    private function syncMaterials(Product $product, array $materials): void
    {
        $sync = [];
        foreach ($materials as $materialData) {
            $material = Material::firstOrCreate([
                'name' => $materialData['name'],
                'material_type' => $materialData['type'],
            ]);
            $sync[$material->id] = [
                'percentage' => $materialData['percentage'] ?? null,
                'notes' => $materialData['notes'] ?? null,
            ];
        }
        $product->materials()->sync($sync);
    }

    private function syncGemstones(Product $product, ProductVariant $variant, array $gemstones): void
    {
        $sync = [];
        foreach ($gemstones as $stoneData) {
            $stone = Gemstone::firstOrCreate(['name' => $stoneData['name']], ['hardness_mohs' => $stoneData['hardness']]);
            $sync[$stone->id] = [
                'product_variant_id' => $variant->id,
                'quantity' => $stoneData['quantity'],
                'total_carat' => $stoneData['carat'] ?? null,
                'cut_grade' => $stoneData['cut'] ?? null,
                'color_grade' => $stoneData['color'] ?? null,
                'clarity_grade' => $stoneData['clarity'] ?? null,
                'setting_type' => $stoneData['setting'] ?? null,
            ];
        }
        $product->gemstones()->sync($sync);
    }

    private function records(): array
    {
        $watchDescription = 'Thông số kích thước, bộ máy, kính, khả năng chống nước và bảo hành được trình bày rõ ràng để khách dễ so sánh.';
        $jewelryDescription = 'Thiết kế hoàn thiện thủ công, kèm thông tin chất liệu, kích thước, trọng lượng và hướng dẫn bảo quản minh bạch.';
        $watchImages = [
            'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1547996160-81dfa63595aa?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1612817159949-195b6eb9e31a?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1434056886845-dac89ffe9b56?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1622434641406-a158123450f9?auto=format&fit=crop&w=1200&q=85',
        ];
        $jewelryImages = [
            'https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1611652022419-a9419f74343d?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?auto=format&fit=crop&w=1200&q=85',
            'https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=1200&q=85',
        ];

        return [
            $this->watch('Tissot PRX Powermatic 80 40mm', 'tissot-prx-powermatic-80-40mm', 'tissot', 'automatic-watches', 'TIS-PRX-P80', 'Mặt xanh / Dây thép 40 mm', 21800000, $watchImages[0], 7, ['movement' => 'Automatic', 'caliber' => 'Powermatic 80 (C07.111)', 'case_material' => 'Thép không gỉ 316L', 'case_diameter_mm' => 40, 'case_thickness_mm' => 10.9, 'dial_color' => 'Xanh navy', 'water_resistance_m' => 100, 'crystal' => 'Sapphire', 'strap_material' => 'Thép không gỉ', 'strap_color' => 'Bạc', 'clasp_type' => 'Bướm có nút bấm', 'power_reserve_hours' => 80, 'functions' => ['Giờ, phút, giây', 'Lịch ngày'], 'warranty_months' => 24], $watchDescription, 'https://luxshopping.vn/'),
            $this->watch('Orient Bambino 38 Small Seconds', 'orient-bambino-38-small-seconds', 'orient', 'dress-watches', 'ORI-RA-AP0105Y', 'Mặt kem / Dây da nâu 38.4 mm', 9850000, $watchImages[1], 9, ['movement' => 'Automatic with manual winding', 'caliber' => 'F6222', 'case_material' => 'Thép không gỉ', 'case_diameter_mm' => 38.4, 'case_thickness_mm' => 12, 'dial_color' => 'Kem', 'water_resistance_m' => 30, 'crystal' => 'Mineral cong', 'strap_material' => 'Da', 'strap_color' => 'Nâu', 'clasp_type' => 'Khóa kim', 'power_reserve_hours' => 40, 'functions' => ['Kim giây nhỏ', 'Lịch ngày'], 'warranty_months' => 24], $watchDescription, 'https://doseco.vn/'),
            $this->watch('Seiko 5 Sports SRPD55K1', 'seiko-5-sports-srpd55k1', 'seiko', 'sport-watches', 'SEI-SRPD55K1', 'Mặt đen / Dây thép 42.5 mm', 7890000, $watchImages[2], 12, ['movement' => 'Automatic with manual winding', 'caliber' => '4R36', 'case_material' => 'Thép không gỉ', 'case_diameter_mm' => 42.5, 'case_thickness_mm' => 13.4, 'dial_color' => 'Đen', 'water_resistance_m' => 100, 'crystal' => 'Hardlex', 'strap_material' => 'Thép không gỉ', 'strap_color' => 'Bạc', 'clasp_type' => 'Khóa gập an toàn', 'power_reserve_hours' => 41, 'functions' => ['Lịch thứ và ngày', 'Dừng kim giây'], 'warranty_months' => 24], $watchDescription, 'https://doseco.vn/'),
            $this->watch('Citizen Eco-Drive Field BM8180', 'citizen-eco-drive-field-bm8180', 'citizen', 'sport-watches', 'CIT-BM8180', 'Mặt đen / Dây canvas xanh 37.2 mm', 5380000, $watchImages[3], 10, ['movement' => 'Eco-Drive', 'caliber' => 'E100', 'case_material' => 'Thép không gỉ', 'case_diameter_mm' => 37.2, 'case_thickness_mm' => 9.5, 'dial_color' => 'Đen', 'water_resistance_m' => 100, 'crystal' => 'Mineral', 'strap_material' => 'Canvas', 'strap_color' => 'Xanh olive', 'clasp_type' => 'Khóa kim', 'power_reserve_hours' => 4320, 'functions' => ['Lịch thứ và ngày', 'Dạ quang'], 'warranty_months' => 36], $watchDescription, 'https://doseco.vn/'),
            $this->watch('Tissot Le Locle Powermatic 80', 'tissot-le-locle-powermatic-80', 'tissot', 'dress-watches', 'TIS-LELOCLE-P80', 'Mặt bạc / Dây da đen 39.3 mm', 19950000, $watchImages[4], 6, ['movement' => 'Automatic', 'caliber' => 'Powermatic 80 (C07.111)', 'case_material' => 'Thép không gỉ 316L', 'case_diameter_mm' => 39.3, 'case_thickness_mm' => 9.8, 'dial_color' => 'Bạc', 'water_resistance_m' => 30, 'crystal' => 'Sapphire', 'strap_material' => 'Da', 'strap_color' => 'Đen', 'clasp_type' => 'Bướm', 'power_reserve_hours' => 80, 'functions' => ['Lịch ngày', 'Mặt đáy lộ máy'], 'warranty_months' => 24], $watchDescription, 'https://luxshopping.vn/'),
            $this->watch('Orient Kamasu Red Dial', 'orient-kamasu-red-dial', 'orient', 'sport-watches', 'ORI-RA-AA0003R', 'Mặt đỏ / Dây thép 41.8 mm', 11200000, $watchImages[5], 8, ['movement' => 'Automatic with manual winding', 'caliber' => 'F6922', 'case_material' => 'Thép không gỉ', 'case_diameter_mm' => 41.8, 'case_thickness_mm' => 12.8, 'dial_color' => 'Đỏ burgundy', 'water_resistance_m' => 200, 'crystal' => 'Sapphire', 'strap_material' => 'Thép không gỉ', 'strap_color' => 'Bạc', 'clasp_type' => 'Khóa gập an toàn', 'power_reserve_hours' => 40, 'functions' => ['Lịch thứ và ngày', 'Bezel xoay một chiều'], 'warranty_months' => 24], $watchDescription, 'https://doseco.vn/'),
            $this->watch('Casio Edifice Slim Sapphire', 'casio-edifice-slim-sapphire', 'casio', 'sport-watches', 'CAS-EFR-S108D', 'Mặt xanh / Dây thép 39.9 mm', 4980000, $watchImages[6], 15, ['movement' => 'Quartz', 'caliber' => 'Module 5359', 'case_material' => 'Thép không gỉ', 'case_diameter_mm' => 39.9, 'case_thickness_mm' => 7.8, 'dial_color' => 'Xanh navy', 'water_resistance_m' => 100, 'crystal' => 'Sapphire', 'strap_material' => 'Thép không gỉ', 'strap_color' => 'Bạc', 'clasp_type' => 'Khóa gập ba', 'functions' => ['Lịch ngày', 'Dạ quang'], 'warranty_months' => 12], $watchDescription, 'https://doseco.vn/'),

            $this->jewelry('Celeste Diamond Halo Ring', 'celeste-diamond-halo-ring', 'rings', 'LJA-RG-CEL-18W', 'Vàng trắng 18K / Cỡ EU 52', 28900000, $jewelryImages[0], 4, ['jewelry_type' => 'ring', 'gender' => 'women', 'style' => 'Halo', 'ring_size_system' => 'EU', 'dimensions' => 'Mặt nhẫn 8.2 mm', 'total_weight_grams' => 3.4, 'plating' => 'Rhodium', 'care_instructions' => 'Kiểm tra chấu đá định kỳ 6 tháng; tránh hóa chất và va đập mạnh.'], [['name' => 'Vàng trắng 18K', 'type' => 'metal', 'percentage' => 75]], [['name' => 'Kim cương', 'hardness' => 10, 'quantity' => 17, 'carat' => .25, 'cut' => 'Very Good', 'color' => 'G-H', 'clarity' => 'VS-SI', 'setting' => 'Halo']], $jewelryDescription, 'https://trangsuc.doji.vn/'),
            $this->jewelry('Aurora Pearl Drop Earrings', 'aurora-pearl-drop-earrings', 'earrings', 'LJA-ER-AUR-14Y', 'Vàng 14K / Ngọc trai 7 mm', 8950000, $jewelryImages[1], 7, ['jewelry_type' => 'earrings', 'gender' => 'women', 'style' => 'Drop', 'dimensions' => 'Dài 24 mm; ngọc trai 7 mm', 'total_weight_grams' => 3.1, 'care_instructions' => 'Lau ngọc trai bằng khăn mềm ẩm; đeo sau khi dùng mỹ phẩm và nước hoa.'], [['name' => 'Vàng vàng 14K', 'type' => 'metal', 'percentage' => 58.5]], [['name' => 'Ngọc trai nuôi cấy', 'hardness' => 3, 'quantity' => 2, 'setting' => 'Chốt xuyên']], $jewelryDescription, 'https://huythanhjewelry.vn/'),
            $this->jewelry('Élan Tennis Bracelet', 'elan-tennis-bracelet', 'bracelets', 'LJA-BR-ELA-S925', 'Bạc 925 / Dài 17 cm', 4250000, $jewelryImages[2], 9, ['jewelry_type' => 'bracelet', 'gender' => 'women', 'style' => 'Tennis', 'bracelet_length_mm' => 170, 'dimensions' => 'Bản rộng 2.5 mm', 'total_weight_grams' => 7.8, 'plating' => 'Rhodium', 'care_instructions' => 'Bảo quản riêng trong túi kín; dùng khăn bạc chuyên dụng để làm sáng.'], [['name' => 'Bạc Sterling 925', 'type' => 'metal', 'percentage' => 92.5]], [['name' => 'Cubic Zirconia', 'hardness' => 8.5, 'quantity' => 42, 'carat' => 3.2, 'cut' => 'Round', 'setting' => 'Four prong']], $jewelryDescription, 'https://trangsuc.doji.vn/'),
            $this->jewelry('Nocturne Sapphire Pendant', 'nocturne-sapphire-pendant', 'pendants', 'LJA-PD-NOC-14W', 'Vàng trắng 14K / Sapphire xanh', 12600000, $jewelryImages[3], 5, ['jewelry_type' => 'pendant', 'gender' => 'women', 'style' => 'Solitaire', 'dimensions' => 'Mặt 11 × 8 mm', 'total_weight_grams' => 2.2, 'plating' => 'Rhodium', 'care_instructions' => 'Rửa bằng nước ấm và xà phòng dịu nhẹ; tránh máy siêu âm khi chấu đá lỏng.'], [['name' => 'Vàng trắng 14K', 'type' => 'metal', 'percentage' => 58.5]], [['name' => 'Sapphire', 'hardness' => 9, 'quantity' => 1, 'carat' => .65, 'cut' => 'Oval', 'color' => 'Royal blue', 'setting' => 'Four prong']], $jewelryDescription, 'https://trangsuc.doji.vn/'),
            $this->jewelry('Solstice Rose Gold Chain', 'solstice-rose-gold-chain', 'necklaces', 'LJA-NK-SOL-18R', 'Vàng hồng 18K / Dài 45 cm', 18400000, $jewelryImages[4], 6, ['jewelry_type' => 'necklace', 'gender' => 'unisex', 'style' => 'Cable chain', 'chain_length_mm' => 450, 'dimensions' => 'Mắt xích 1.2 mm', 'total_weight_grams' => 4.6, 'care_instructions' => 'Tháo khi vận động và ngủ; cất phẳng để tránh xoắn dây.'], [['name' => 'Vàng hồng 18K', 'type' => 'metal', 'percentage' => 75]], [], $jewelryDescription, 'https://huythanhjewelry.vn/'),
            $this->jewelry('Eternal Pair Wedding Bands', 'eternal-pair-wedding-bands', 'rings', 'LJA-RG-ETR-14Y', 'Cặp nhẫn vàng 14K / EU 52 & 60', 22900000, $jewelryImages[5], 4, ['jewelry_type' => 'ring', 'gender' => 'unisex', 'style' => 'Wedding band', 'ring_size_system' => 'EU', 'dimensions' => 'Bản nữ 2.5 mm; bản nam 3.5 mm', 'total_weight_grams' => 7.2, 'care_instructions' => 'Làm sạch và đánh bóng chuyên nghiệp định kỳ; tránh tiếp xúc chlorine.'], [['name' => 'Vàng vàng 14K', 'type' => 'metal', 'percentage' => 58.5]], [], $jewelryDescription, 'https://huythanhjewelry.vn/'),
            $this->jewelry('Moonlit Clover Bracelet', 'moonlit-clover-bracelet', 'bracelets', 'LJA-BR-MOO-14Y', 'Vàng 14K / Xà cừ trắng 16–18 cm', 11800000, $jewelryImages[6], 8, ['jewelry_type' => 'bracelet', 'gender' => 'women', 'style' => 'Clover station', 'bracelet_length_mm' => 180, 'dimensions' => '5 họa tiết cỏ bốn lá 8 mm; tăng đơ 20 mm', 'total_weight_grams' => 3.7, 'care_instructions' => 'Tránh ngâm nước lâu và hóa chất; lau khô bằng khăn mềm sau khi đeo.'], [['name' => 'Vàng vàng 14K', 'type' => 'metal', 'percentage' => 58.5], ['name' => 'Xà cừ', 'type' => 'other']], [], $jewelryDescription, 'https://trangsuc.doji.vn/'),
        ];
    }

    private function watch(string $name, string $slug, string $brand, string $category, string $sku, string $variant, int $price, string $image, int $stock, array $detail, string $description, string $source): array
    {
        return compact('name', 'slug', 'brand', 'category', 'sku', 'variant', 'price', 'image', 'stock', 'detail', 'source') + [
            'type' => 'watch',
            'short' => $detail['movement'].' · '.$detail['case_diameter_mm'].' mm · chống nước '.$detail['water_resistance_m'].' m · kính '.$detail['crystal'].'.',
            'description' => $description,
        ];
    }

    private function jewelry(string $name, string $slug, string $category, string $sku, string $variant, int $price, string $image, int $stock, array $detail, array $materials, array $gemstones, string $description, string $source): array
    {
        return compact('name', 'slug', 'category', 'sku', 'variant', 'price', 'image', 'stock', 'detail', 'materials', 'gemstones', 'source') + [
            'brand' => 'lunar-atelier',
            'type' => 'jewelry',
            'short' => $variant.' · '.($detail['dimensions'] ?? 'Hoàn thiện thủ công').'.',
            'description' => $description,
            'weight' => $detail['total_weight_grams'] ?? null,
        ];
    }
}
