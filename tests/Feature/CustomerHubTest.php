<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerHubTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'LJ-TEST-'.strtoupper(fake()->unique()->bothify('??###')),
            'user_id' => $user->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'VND',
            'subtotal_amount' => 2500000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 2500000,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0900000001',
            'placed_at' => now(),
        ], $overrides));
    }

    public function test_customer_hub_routes_require_login_and_render_for_the_owner(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['name' => 'Lunar Client', 'status' => 'active']);
        $order = $this->order($user);

        $this->get(route('account.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('MY LUNAR')
            ->assertSee($order->order_number);

        foreach (['account.orders', 'account.notifications', 'account.benefits', 'account.wishlist', 'account.after-sales', 'account.profile'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_order_notifications_are_private_and_can_be_marked_read(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $order = $this->order($owner);
        $otherOrder = $this->order($other);

        $order->update(['status' => 'preparing']);

        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame('order', $owner->notifications()->latest()->first()->data['category']);

        $otherNotification = $other->notifications()->firstOrFail();
        $this->actingAs($owner)
            ->post(route('account.notifications.read', $otherNotification->id))
            ->assertNotFound();

        $notification = $owner->notifications()->latest()->firstOrFail();
        $this->actingAs($owner)
            ->post(route('account.notifications.read', $notification->id))
            ->assertRedirect(route('account.orders.show', $order));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNotNull($otherOrder);
    }

    public function test_customer_can_create_after_sales_request_only_for_own_order(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $order = $this->order($user, ['status' => 'completed']);
        $otherOrder = $this->order($other, ['status' => 'completed']);

        $this->actingAs($user)->post(route('account.after-sales.returns.store'), [
            'order_id' => $order->id,
            'reason' => 'Kích thước sản phẩm không phù hợp.',
        ])->assertRedirect();

        $this->assertDatabaseHas('return_requests', ['order_id' => $order->id, 'status' => 'requested']);

        $this->actingAs($user)->post(route('account.after-sales.returns.store'), [
            'order_id' => $otherOrder->id,
            'reason' => 'Không thuộc đơn của tài khoản này.',
        ])->assertNotFound();
        $this->assertDatabaseMissing('return_requests', ['order_id' => $otherOrder->id]);
    }

    public function test_wishlist_is_scoped_to_the_logged_in_customer(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $category = Category::create(['name' => 'Trang sức', 'slug' => 'jewelry', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Lunar', 'slug' => 'lunar', 'is_active' => true]);
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Moonlight Ring',
            'slug' => 'moonlight-ring',
            'product_type' => 'jewelry',
            'status' => 'active',
            'base_price_amount' => 3500000,
            'currency' => 'VND',
        ]);

        $this->actingAs($user)->post(route('account.wishlist.toggle', $product))->assertRedirect();
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id]);
        $this->assertDatabaseCount('wishlist_items', 1);

        $this->actingAs($other)->get(route('account.wishlist'))
            ->assertOk()
            ->assertDontSee('Moonlight Ring');
    }
}
