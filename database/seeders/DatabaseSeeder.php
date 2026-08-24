<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\JewelryDetail;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\SupportFaq;
use App\Models\SupportSavedReply;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WatchDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'admin.access',
            'dashboard.view',
            'catalog.view', 'catalog.create', 'catalog.update', 'catalog.delete',
            'inventory.view', 'inventory.adjust', 'inventory.receive',
            'orders.view', 'orders.update_status', 'orders.add_note', 'orders.print_invoice',
            'customers.view', 'customers.update', 'customers.block',
            'payments.view', 'payments.refund',
            'shipping.view', 'shipping.manage',
            'promotions.manage', 'reviews.moderate', 'warranties.manage', 'returns.manage',
            'content.manage', 'reports.view', 'reports.export', 'settings.manage', 'staff.manage',
            'support.view', 'support.reply', 'support.assign', 'support.resolve', 'support.manage_knowledge',
        ];

        $permissionModels = collect($permissions)->mapWithKeys(fn (string $slug) => [
            $slug => Permission::updateOrCreate(['slug' => $slug], ['name' => str($slug)->replace('.', ' ')->title()]),
        ]);

        $roles = [
            'super-admin' => $permissions,
            'product-manager' => ['admin.access', 'dashboard.view', 'catalog.view', 'catalog.create', 'catalog.update', 'catalog.delete'],
            'inventory-clerk' => ['admin.access', 'dashboard.view', 'inventory.view', 'inventory.adjust', 'inventory.receive', 'catalog.view'],
            'order-manager' => ['admin.access', 'dashboard.view', 'orders.view', 'orders.update_status', 'orders.add_note', 'orders.print_invoice', 'shipping.view', 'shipping.manage'],
            'customer-service' => ['admin.access', 'dashboard.view', 'customers.view', 'customers.update', 'customers.block', 'reviews.moderate', 'warranties.manage', 'returns.manage', 'orders.view', 'orders.add_note', 'support.view', 'support.reply', 'support.assign', 'support.resolve', 'support.manage_knowledge'],
            'marketing' => ['admin.access', 'dashboard.view', 'catalog.view', 'promotions.manage', 'content.manage', 'reviews.moderate'],
            'accountant' => ['admin.access', 'dashboard.view', 'payments.view', 'payments.refund', 'reports.view', 'reports.export', 'orders.view'],
        ];

        foreach ($roles as $slug => $rolePermissions) {
            $role = Role::updateOrCreate(['slug' => $slug], [
                'name' => str($slug)->replace('-', ' ')->title(), 'is_staff' => true, 'is_system' => true,
            ]);
            $role->permissions()->sync($permissionModels->only($rolePermissions)->pluck('id'));
        }

        $admin = User::updateOrCreate(['email' => 'admin@lunarjewels.test'], [
            'name' => 'LUNAR JEWELS Admin', 'phone' => '0900000000', 'password' => 'password', 'status' => 'active', 'email_verified_at' => now(),
        ]);
        $admin->roles()->syncWithoutDetaching([Role::where('slug', 'super-admin')->value('id')]);

        $supportFaqs = [
            [
                'slug' => 'shipping-fee',
                'question' => 'Phí vận chuyển được tính thế nào?',
                'answer' => 'Phí vận chuyển được tính theo khu vực giao hàng và hiển thị trước khi bạn đặt đơn. Khi đơn đạt ngưỡng miễn phí của khu vực, hệ thống sẽ tự động miễn phí vận chuyển.',
                'keywords' => ['phí vận chuyển', 'phí ship', 'giao hàng', 'miễn phí vận chuyển'],
                'category' => 'shipping',
                'suggestions' => ['Tôi muốn theo dõi đơn hàng', 'Chính sách đổi trả'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'track-order',
                'question' => 'Tôi muốn theo dõi đơn hàng',
                'answer' => 'Nếu đã đăng nhập, bạn có thể hỏi mã đơn hoặc đơn gần nhất ngay tại đây. Khách chưa đăng nhập vui lòng dùng trang Theo dõi đơn hàng để xác minh thông tin mua hàng.',
                'keywords' => ['theo dõi đơn', 'trạng thái đơn', 'đơn của tôi'],
                'category' => 'order',
                'suggestions' => ['Chính sách đổi trả', 'Tôi cần bảo hành sản phẩm'],
                'sort_order' => 20,
            ],
            [
                'slug' => 'returns',
                'question' => 'Chính sách đổi trả',
                'answer' => 'Bạn có thể gửi yêu cầu tại mục Đổi trả. Hệ thống sẽ yêu cầu thông tin xác minh đơn hàng và đội ngũ chăm sóc khách hàng sẽ phản hồi sau khi kiểm tra tình trạng sản phẩm.',
                'keywords' => ['đổi trả', 'đổi hàng', 'trả hàng', 'hoàn hàng'],
                'category' => 'returns',
                'suggestions' => ['Tôi cần bảo hành sản phẩm', 'Gặp nhân viên'],
                'sort_order' => 30,
            ],
            [
                'slug' => 'warranty',
                'question' => 'Tôi cần bảo hành sản phẩm',
                'answer' => 'Bạn có thể gửi yêu cầu tại mục Bảo hành bằng mã phiếu bảo hành và thông tin mua hàng. Nhân viên sẽ kiểm tra hiệu lực rồi cập nhật tiến độ xử lý.',
                'keywords' => ['bảo hành', 'sửa chữa', 'phiếu bảo hành'],
                'category' => 'warranty',
                'suggestions' => ['Chính sách đổi trả', 'Gặp nhân viên'],
                'sort_order' => 40,
            ],
            [
                'slug' => 'payments',
                'question' => 'LUNAR hỗ trợ phương thức thanh toán nào?',
                'answer' => 'Các phương thức đang khả dụng sẽ được hiển thị ở bước thanh toán. Trạng thái thanh toán của đơn đã đăng nhập có thể được tra cứu an toàn ngay trong cuộc trò chuyện.',
                'keywords' => ['thanh toán', 'vnpay', 'paypal', 'chuyển khoản', 'cod'],
                'category' => 'payment',
                'suggestions' => ['Tôi muốn theo dõi đơn hàng', 'Gặp nhân viên'],
                'sort_order' => 50,
            ],
        ];

        foreach ($supportFaqs as $faq) {
            SupportFaq::updateOrCreate(['slug' => $faq['slug']], [...$faq, 'is_active' => true]);
        }

        $savedReplies = [
            ['shortcut' => 'da-tiep-nhan', 'title' => 'Đã tiếp nhận', 'body' => 'LUNAR đã tiếp nhận yêu cầu của bạn và đang kiểm tra thông tin. Mình sẽ phản hồi ngay khi có cập nhật.', 'category' => 'general', 'sort_order' => 10],
            ['shortcut' => 'can-them-thong-tin', 'title' => 'Cần thêm thông tin', 'body' => 'Bạn vui lòng cung cấp thêm mã đơn hàng và mô tả chi tiết tình huống để LUNAR kiểm tra chính xác hơn.', 'category' => 'general', 'sort_order' => 20],
            ['shortcut' => 'da-giai-quyet', 'title' => 'Đã giải quyết', 'body' => 'Yêu cầu đã được xử lý. Nếu bạn cần hỗ trợ thêm, hãy nhắn lại trong cuộc trò chuyện này.', 'category' => 'general', 'sort_order' => 30],
        ];

        foreach ($savedReplies as $reply) {
            SupportSavedReply::updateOrCreate(['shortcut' => $reply['shortcut']], [...$reply, 'is_active' => true]);
        }

        $watches = Category::updateOrCreate(['slug' => 'watches'], ['name' => 'Đồng hồ', 'description' => 'Đồng hồ chính hãng.', 'is_active' => true, 'sort_order' => 1]);
        $automatic = Category::updateOrCreate(['slug' => 'automatic-watches'], ['name' => 'Đồng hồ cơ', 'parent_id' => $watches->id, 'is_active' => true, 'sort_order' => 1]);
        $dress = Category::updateOrCreate(['slug' => 'dress-watches'], ['name' => 'Đồng hồ dress watch', 'parent_id' => $watches->id, 'is_active' => true, 'sort_order' => 2]);
        $jewelry = Category::updateOrCreate(['slug' => 'jewelry'], ['name' => 'Trang sức', 'description' => 'Trang sức tinh tế mỗi ngày.', 'is_active' => true, 'sort_order' => 2]);
        $rings = Category::updateOrCreate(['slug' => 'rings'], ['name' => 'Nhẫn', 'parent_id' => $jewelry->id, 'is_active' => true, 'sort_order' => 1]);
        $earrings = Category::updateOrCreate(['slug' => 'earrings'], ['name' => 'Bông tai', 'parent_id' => $jewelry->id, 'is_active' => true, 'sort_order' => 2]);

        $seiko = Brand::updateOrCreate(['slug' => 'seiko'], ['name' => 'Seiko', 'website' => 'https://www.seikowatches.com/', 'is_active' => true]);
        $citizen = Brand::updateOrCreate(['slug' => 'citizen'], ['name' => 'Citizen', 'website' => 'https://www.citizenwatch.com/', 'is_active' => true]);
        $pandora = Brand::updateOrCreate(['slug' => 'pandora'], ['name' => 'Pandora', 'website' => 'https://us.pandora.net/', 'is_active' => true]);
        $mejuri = Brand::updateOrCreate(['slug' => 'mejuri'], ['name' => 'Mejuri', 'website' => 'https://mejuri.com/', 'is_active' => true]);

        $records = [
            ['brand' => $seiko, 'category' => $dress, 'name' => 'Seiko Presage Cocktail Time SRPB43', 'slug' => 'seiko-presage-cocktail-time-srpb43', 'type' => 'watch', 'sku' => 'SEI-SRPB43', 'variant' => 'Mặt xanh nhạt / Dây da đen', 'price' => 11250000, 'image' => 'https://seikousa.com/cdn/shop/files/SRPB43_1_91811358-199c-4511-af1c-24d997ab93ad.png?v=1787072328&width=1946', 'source' => 'https://seikousa.com/products/srpb43', 'detail' => ['movement' => 'Automatic with manual winding', 'caliber' => '4R35', 'case_material' => 'Thép không gỉ', 'case_diameter_mm' => 40.5, 'case_thickness_mm' => 11.8, 'dial_color' => 'Xanh nhạt', 'water_resistance_m' => 50, 'crystal' => 'Hardlex dạng hộp', 'strap_material' => 'Da', 'strap_color' => 'Đen', 'clasp_type' => 'Khóa gập có nút bấm', 'power_reserve_hours' => 41, 'functions' => ['Hiển thị ngày', 'Dừng kim giây'], 'warranty_months' => 24]],
            ['brand' => $citizen, 'category' => $automatic, 'name' => 'Citizen TSUYOSA NJ0150-56L', 'slug' => 'citizen-tsuyosa-nj0150-56l', 'type' => 'watch', 'sku' => 'CIT-NJ0150-56L', 'variant' => 'Mặt xanh / Dây thép', 'price' => 12375000, 'image' => 'https://citizenshop.me/assets/beautyshots/shot3/NJ0150-81L.jpg', 'detail' => ['movement' => 'Automatic', 'caliber' => '8210', 'case_material' => 'Thép không gỉ', 'case_diameter_mm' => 40, 'dial_color' => 'Xanh sunray', 'water_resistance_m' => 50, 'crystal' => 'Sapphire', 'strap_material' => 'Thép không gỉ', 'clasp_type' => 'Khóa bấm', 'functions' => ['Hiển thị ngày'], 'warranty_months' => 24]],
            ['brand' => $pandora, 'category' => $rings, 'name' => 'Pandora Row of Hearts Ring', 'slug' => 'pandora-row-of-hearts-ring', 'type' => 'jewelry', 'sku' => 'PAN-193427C00-54', 'variant' => 'Cỡ EU 54 / Bạc 925', 'price' => 1375000, 'image' => 'https://burrowsjewellers.com.au/cdn/shop/files/8e74659b-88c6-4f8d-b150-bc1821b034ae_1200x.jpg?v=1748604897', 'detail' => ['jewelry_type' => 'ring', 'gender' => 'women', 'style' => 'Stacking', 'ring_size_system' => 'EU', 'care_instructions' => 'Tháo trang sức trước khi bơi và bảo quản riêng.']],
            ['brand' => $mejuri, 'category' => $earrings, 'name' => 'Mejuri Mini Hoops', 'slug' => 'mejuri-mini-hoops', 'type' => 'jewelry', 'sku' => 'MEJ-MINI-HOOP', 'variant' => '10 mm / Vàng 14K', 'price' => 2200000, 'image' => 'https://images.unsplash.com/photo-1673131158657-4404fd1f041a?auto=format&fit=crop&w=1200&q=85', 'detail' => ['jewelry_type' => 'earrings', 'gender' => 'unisex', 'style' => 'Hoop', 'dimensions' => 'Đường kính ngoài 10 mm', 'care_instructions' => 'Lau bằng khăn mềm sau khi sử dụng.']],
        ];

        $warehouse = Warehouse::updateOrCreate(['code' => 'HCM-01'], ['name' => 'Kho chính TP. Hồ Chí Minh', 'province' => 'Hồ Chí Minh', 'country_code' => 'VN', 'is_active' => true]);
        foreach ($records as $record) {
            $product = Product::updateOrCreate(['slug' => $record['slug']], ['brand_id' => $record['brand']->id, 'category_id' => $record['category']->id, 'name' => $record['name'], 'product_type' => $record['type'], 'short_description' => 'Sản phẩm tham khảo dành cho bộ sưu tập LUNAR JEWELS.', 'description' => 'Thiết kế được tuyển chọn với thông số minh bạch, phù hợp để đeo hằng ngày hoặc làm quà tặng.', 'status' => 'active', 'base_price_amount' => $record['price'], 'currency' => 'VND', 'is_featured' => true, 'source_url' => $record['source'] ?? null]);
            $variant = ProductVariant::updateOrCreate(['sku' => $record['sku']], ['product_id' => $product->id, 'name' => $record['variant'], 'price_amount' => $record['price'], 'status' => 'active']);
            ProductImage::updateOrCreate(['product_id' => $product->id, 'is_primary' => true], ['product_variant_id' => $variant->id, 'storage_disk' => 'external', 'path' => $record['image'], 'alt_text' => $record['name'], 'sort_order' => 1, 'is_licensed' => false]);
            Inventory::updateOrCreate(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id], ['quantity_on_hand' => 8, 'quantity_reserved' => 0, 'reorder_level' => 2]);
            if ($record['type'] === 'watch') {
                WatchDetail::updateOrCreate(['product_id' => $product->id], $record['detail']);
            } else {
                JewelryDetail::updateOrCreate(['product_id' => $product->id], $record['detail']);
            }
        }

        \DB::table('store_settings')->updateOrInsert(['key' => 'paypal_vnd_usd_rate'], ['value' => '25000', 'updated_at' => now(), 'created_at' => now()]);

        $this->call([
            CatalogExpansionSeeder::class,
            ProductGallerySeeder::class,
        ]);
    }
}
