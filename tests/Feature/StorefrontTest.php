<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\JewelryDetail;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Models\User;
use App\Models\VietnamProvince;
use App\Models\VietnamWard;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create(['name' => 'Đồng hồ', 'slug' => 'watches', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Lunar', 'slug' => 'lunar', 'is_active' => true]);
        $product = Product::create(['brand_id' => $brand->id, 'category_id' => $category->id, 'name' => 'Lunar Automatic', 'slug' => 'lunar-automatic', 'product_type' => 'watch', 'status' => 'active', 'base_price_amount' => 5000000, 'currency' => 'VND', 'is_featured' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'LUNAR-AUTO-01', 'name' => 'Mặt xanh', 'price_amount' => 5000000, 'status' => 'active']);
        $warehouse = Warehouse::create(['code' => 'TEST', 'name' => 'Kho test', 'country_code' => 'VN', 'is_active' => true]);
        Inventory::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity_on_hand' => 4, 'quantity_reserved' => 0]);

        return $product;
    }

    /** @return array{0: Order, 1: Payment} */
    private function paypalOrder(): array
    {
        $order = Order::create([
            'order_number' => 'PAYPAL-TEST-01',
            'status' => 'pending_confirmation',
            'payment_status' => 'pending',
            'currency' => 'VND',
            'subtotal_amount' => 100000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 100000,
            'customer_name' => 'PayPal Buyer',
            'customer_email' => 'buyer@example.test',
            'customer_phone' => '0900000001',
            'placed_at' => now(),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'paypal',
            'payment_method' => 'paypal',
            'transaction_id' => 'PAYPAL-SANDBOX-ORDER',
            'amount' => 100000,
            'currency' => 'VND',
            'payment_currency' => 'USD',
            'provider_amount' => 4,
            'exchange_rate' => 25000,
            'status' => 'pending',
        ]);

        config([
            'services.paypal.client_id' => 'sandbox-client',
            'services.paypal.secret' => 'sandbox-secret',
            'services.paypal.base_url' => 'https://api-m.sandbox.paypal.com',
        ]);

        return [$order, $payment];
    }

    public function test_guest_can_browse_add_to_cart_and_place_cod_order(): void
    {
        $this->withoutVite();
        $product = $this->product();
        $variant = $product->variants()->first();
        $zone = ShippingZone::create(['code' => 'test', 'name' => 'Khu vực test', 'fee_vnd' => 30000, 'free_shipping_threshold_vnd' => 5000000, 'is_active' => true]);
        VietnamProvince::create(['code' => '79', 'name' => 'Hồ Chí Minh', 'full_name' => 'Thành phố Hồ Chí Minh', 'shipping_zone_id' => $zone->id]);
        VietnamWard::create(['code' => '26734', 'province_code' => '79', 'name' => 'Bến Nghé', 'full_name' => 'Phường Bến Nghé', 'shipping_zone_id' => $zone->id]);
        $this->get(route('shop', ['filter' => ['brand' => [$product->brand_id]]]))->assertOk()->assertSee('Lunar Automatic');
        $this->post(route('cart.store', $product), ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertOk()->assertSee('Lunar Automatic');
        $this->post(route('checkout.shipping.store'), ['shipping' => ['recipient_name' => 'Nguyen Van A', 'email' => 'a@example.test', 'phone' => '0900000001', 'line_1' => '1 Nguyen Hue', 'province_code' => '79', 'ward_code' => '26734']])->assertRedirect(route('checkout.payment'));
        $this->post(route('checkout.place'), ['shipping_method' => 'standard', 'payment_method' => 'cod'])->assertRedirect();
        $this->assertDatabaseHas('orders', ['customer_phone' => '0900000001', 'payment_status' => 'unpaid', 'status' => 'confirmed']);
    }

    public function test_guest_can_place_bank_transfer_order_and_see_vietqr_instructions(): void
    {
        $this->withoutVite();
        $product = $this->product();
        $variant = $product->variants()->first();
        $zone = ShippingZone::create(['code' => 'test', 'name' => 'Khu vực test', 'fee_vnd' => 30000, 'free_shipping_threshold_vnd' => 5000000, 'is_active' => true]);
        VietnamProvince::create(['code' => '79', 'name' => 'Hồ Chí Minh', 'full_name' => 'Thành phố Hồ Chí Minh', 'shipping_zone_id' => $zone->id]);
        VietnamWard::create(['code' => '26734', 'province_code' => '79', 'name' => 'Bến Nghé', 'full_name' => 'Phường Bến Nghé', 'shipping_zone_id' => $zone->id]);

        $this->post(route('cart.store', $product), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('checkout.shipping.store'), ['shipping' => ['recipient_name' => 'Nguyen Van A', 'email' => 'a@example.test', 'phone' => '0900000001', 'line_1' => '1 Nguyen Hue', 'province_code' => '79', 'ward_code' => '26734']]);
        $response = $this->post(route('checkout.place'), ['shipping_method' => 'standard', 'payment_method' => 'bank_transfer']);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('checkout.confirmation', $order));
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider' => 'bank_transfer', 'status' => 'pending']);
        $this->get(route('checkout.confirmation', $order))->assertOk()->assertSee('Quét mã để chuyển khoản')->assertSee('TECHCOMBANK')->assertSee('img.vietqr.io');

        $this->flushSession();
        $this->get(route('checkout.confirmation', $order))->assertForbidden();
    }

    public function test_unconfigured_gateway_cannot_create_an_order(): void
    {
        $this->withoutVite();
        config(['services.vnpay.tmn_code' => null, 'services.vnpay.hash_secret' => null]);
        $product = $this->product();
        $variant = $product->variants()->first();
        $zone = ShippingZone::create(['code' => 'test', 'name' => 'Khu vực test', 'fee_vnd' => 30000, 'free_shipping_threshold_vnd' => 5000000, 'is_active' => true]);
        VietnamProvince::create(['code' => '79', 'name' => 'Hồ Chí Minh', 'full_name' => 'Thành phố Hồ Chí Minh', 'shipping_zone_id' => $zone->id]);
        VietnamWard::create(['code' => '26734', 'province_code' => '79', 'name' => 'Bến Nghé', 'full_name' => 'Phường Bến Nghé', 'shipping_zone_id' => $zone->id]);

        $this->post(route('cart.store', $product), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('checkout.shipping.store'), ['shipping' => ['recipient_name' => 'Nguyen Van A', 'email' => 'a@example.test', 'phone' => '0900000001', 'line_1' => '1 Nguyen Hue', 'province_code' => '79', 'ward_code' => '26734']]);
        $this->post(route('checkout.place'), ['shipping_method' => 'standard', 'payment_method' => 'vnpay_qr'])
            ->assertRedirect(route('checkout.payment'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_paypal_return_captures_an_approved_order_with_an_explicit_json_body(): void
    {
        [$order, $payment] = $this->paypalOrder();
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-SANDBOX-ORDER/capture' => Http::response(['id' => 'PAYPAL-SANDBOX-ORDER', 'status' => 'COMPLETED'], 201),
        ]);

        $this->get(route('payments.paypal.return', ['token' => $payment->transaction_id]))
            ->assertRedirect(route('checkout.confirmation', $order));

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'status' => 'confirmed']);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-SANDBOX-ORDER/capture'
            && $request->method() === 'POST'
            && $request->header('Content-Type')[0] === 'application/json'
            && $request->body() === '{}');
    }

    public function test_paypal_capture_api_error_keeps_payment_pending_for_reconciliation(): void
    {
        [$order, $payment] = $this->paypalOrder();
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-SANDBOX-ORDER/capture' => Http::response(['name' => 'INVALID_REQUEST', 'message' => 'Malformed capture request'], 400),
        ]);

        $this->get(route('payments.paypal.return', ['token' => $payment->transaction_id]))
            ->assertRedirect(route('checkout.confirmation', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending', 'status' => 'pending_confirmation']);
    }

    public function test_a_fresh_browser_never_displays_another_users_cart_count(): void
    {
        $this->withoutVite();
        $product = $this->product();
        $variant = $product->variants()->first();
        $otherUser = User::create([
            'name' => 'Other buyer',
            'email' => 'other@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $otherCart = Cart::create(['user_id' => $otherUser->id, 'currency' => 'VND']);
        CartItem::create([
            'cart_id' => $otherCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'unit_price_amount' => $variant->price_amount,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<span class="counter">0</span>', false);
    }

    public function test_jewelry_detail_page_displays_material_and_care_specs(): void
    {
        $this->withoutVite();
        $category = Category::create(['name' => 'Nhẫn', 'slug' => 'rings', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Celeste Test Ring',
            'slug' => 'celeste-test-ring',
            'product_type' => 'jewelry',
            'status' => 'active',
            'base_price_amount' => 12000000,
            'currency' => 'VND',
        ]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'RING-TEST', 'name' => 'EU 52', 'price_amount' => 12000000, 'status' => 'active']);
        $warehouse = Warehouse::create(['code' => 'JEWEL-TEST', 'name' => 'Kho trang sức', 'country_code' => 'VN', 'is_active' => true]);
        Inventory::create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'quantity_on_hand' => 1, 'quantity_reserved' => 0]);
        JewelryDetail::create(['product_id' => $product->id, 'jewelry_type' => 'ring', 'dimensions' => 'Mặt nhẫn 8 mm', 'care_instructions' => 'Tránh hóa chất và va đập mạnh.']);
        $material = Material::create(['name' => 'Vàng trắng 18K', 'material_type' => 'metal']);
        $product->materials()->attach($material, ['percentage' => 75]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Vàng trắng 18K 75%')
            ->assertSee('Tránh hóa chất và va đập mạnh.');
    }

    public function test_paypal_connection_failure_releases_the_attempt_and_returns_to_checkout(): void
    {
        [$order, $payment] = $this->paypalOrder();
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['name' => 'SERVICE_UNAVAILABLE'], 503),
        ]);

        $this->withSession(['confirmation_order_ids' => [$order->id]])
            ->get(route('payments.paypal.redirect', $order))
            ->assertRedirect(route('checkout.payment'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'failed']);
    }
}
