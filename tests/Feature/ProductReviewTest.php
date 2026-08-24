<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create(['name' => 'Đồng hồ', 'slug' => 'watches', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Lunar', 'slug' => 'lunar', 'is_active' => true]);
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Lunar Review Watch',
            'slug' => 'lunar-review-watch',
            'product_type' => 'watch',
            'status' => 'active',
            'base_price_amount' => 5000000,
            'currency' => 'VND',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'LUNAR-REVIEW-01',
            'name' => 'Mặt xanh',
            'price_amount' => 5000000,
            'status' => 'active',
        ]);
        $warehouse = Warehouse::create(['code' => 'REVIEW', 'name' => 'Kho review', 'country_code' => 'VN', 'is_active' => true]);
        Inventory::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity_on_hand' => 4, 'quantity_reserved' => 0]);

        return $product;
    }

    private function orderItem(User $user, Product $product, string $status = 'completed'): OrderItem
    {
        $variant = $product->variants()->firstOrFail();
        $order = Order::create([
            'order_number' => 'REVIEW-'.$status.'-'.$user->id,
            'user_id' => $user->id,
            'status' => $status,
            'payment_status' => 'paid',
            'fulfillment_status' => $status === 'completed' ? 'fulfilled' : 'unfulfilled',
            'currency' => 'VND',
            'subtotal_amount' => 5000000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000000,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0900000001',
            'placed_at' => now(),
        ]);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'unit_price_amount' => 5000000,
            'quantity' => 1,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000000,
        ]);
    }

    public function test_completed_purchaser_can_submit_a_verified_review_for_moderation(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['status' => 'active']);
        $item = $this->orderItem($user, $product);

        $this->actingAs($user)
            ->post(route('products.reviews.store', $product), [
                'rating' => 5,
                'title' => 'Hoàn thiện rất đẹp',
                'body' => 'Sản phẩm đúng mô tả, đóng gói cẩn thận và đeo rất vừa tay.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_item_id' => $item->id,
            'rating' => 5,
            'status' => 'pending',
            'verified_purchase' => true,
        ]);
    }

    public function test_customer_without_a_completed_order_cannot_submit_a_review(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['status' => 'active']);
        $this->orderItem($user, $product, 'shipping');

        $this->actingAs($user)
            ->post(route('products.reviews.store', $product), [
                'rating' => 4,
                'body' => 'Đơn hàng vẫn chưa hoàn thành nên chưa được phép đánh giá.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_product_page_only_displays_approved_reviews(): void
    {
        $this->withoutVite();
        $product = $this->product();
        $approvedUser = User::factory()->create(['name' => 'Khách đã mua', 'status' => 'active']);
        $pendingUser = User::factory()->create(['name' => 'Khách chờ duyệt', 'status' => 'active']);
        $approvedItem = $this->orderItem($approvedUser, $product);
        $pendingItem = $this->orderItem($pendingUser, $product);

        Review::create(['user_id' => $approvedUser->id, 'product_id' => $product->id, 'order_item_id' => $approvedItem->id, 'rating' => 5, 'title' => 'Đáng mua', 'body' => 'Nội dung đánh giá đã được duyệt.', 'status' => 'approved', 'verified_purchase' => true]);
        Review::create(['user_id' => $pendingUser->id, 'product_id' => $product->id, 'order_item_id' => $pendingItem->id, 'rating' => 1, 'title' => 'Chưa công khai', 'body' => 'Nội dung này đang chờ duyệt.', 'status' => 'pending', 'verified_purchase' => true]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Đáng mua')
            ->assertSee('Đã mua hàng')
            ->assertDontSee('Chưa công khai');
    }
}
