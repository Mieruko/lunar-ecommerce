<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Services\OrderStatusService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderCancellationStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_an_unpaid_order_releases_reserved_stock(): void
    {
        $this->seed();
        [$order, $inventory] = $this->makeOrderWithReservation('active');

        app(OrderStatusService::class)->transition($order, 'cancelled', null, 'Khách yêu cầu hủy.');

        $this->assertSame(8, $inventory->fresh()->quantity_on_hand);
        $this->assertSame(0, $inventory->fresh()->quantity_reserved);
        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Khách yêu cầu hủy.',
        ]);
        $this->assertNotNull($order->fresh()->stock_reverted_at);
    }

    public function test_cancelling_a_paid_order_restocks_converted_stock_once(): void
    {
        $this->seed();
        [$order, $inventory] = $this->makeOrderWithReservation('converted');

        app(OrderStatusService::class)->transition($order, 'cancelled', null, 'Sản phẩm lỗi trước khi gửi.');
        app(StockService::class)->restoreForCancellation($order->fresh());

        $this->assertSame(8, $inventory->fresh()->quantity_on_hand);
        $this->assertSame(0, $inventory->fresh()->quantity_reserved);
        $this->assertSame(1, InventoryTransaction::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('transaction_type', 'return')
            ->count());
    }

    public function test_cancellation_requires_a_reason(): void
    {
        $this->seed();
        [$order] = $this->makeOrderWithReservation('active');

        $this->expectException(ValidationException::class);

        app(OrderStatusService::class)->transition($order, 'cancelled');
    }

    public function test_preparing_an_order_creates_a_trackable_shipment(): void
    {
        $this->seed();
        [$order] = $this->makeOrderWithReservation('active');

        app(OrderStatusService::class)->transition($order, 'preparing');

        $shipment = $order->shipments()->firstOrFail();
        $this->assertSame('LUNAR Fulfillment', $shipment->carrier);
        $this->assertMatchesRegularExpression('/^LJ-\d{6}-\d{6}-[A-Z0-9]{4}$/', $shipment->tracking_number);
        $this->assertSame('pending', $shipment->status);

        app(OrderStatusService::class)->transition($order->fresh(), 'cancelled', null, 'Khách đổi ý trước khi giao.');
        $this->assertSame('cancelled', $shipment->fresh()->status);
    }

    private function makeOrderWithReservation(string $reservationStatus): array
    {
        $variant = ProductVariant::query()->with('product')->firstOrFail();
        $inventory = Inventory::query()->where('product_variant_id', $variant->id)->firstOrFail();
        $quantity = 2;

        $inventory->update([
            'quantity_on_hand' => $reservationStatus === 'converted' ? 6 : 8,
            'quantity_reserved' => $reservationStatus === 'active' ? $quantity : 0,
        ]);

        $order = Order::create([
            'order_number' => 'TEST-'.str()->random(10),
            'status' => 'confirmed',
            'payment_status' => $reservationStatus === 'converted' ? 'paid' : 'unpaid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'VND',
            'subtotal_amount' => $variant->price_amount * $quantity,
            'total_amount' => $variant->price_amount * $quantity,
            'customer_name' => 'Khách thử nghiệm',
            'customer_email' => 'customer@example.test',
            'customer_phone' => '0900000001',
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name,
            'unit_price_amount' => $variant->price_amount,
            'quantity' => $quantity,
            'total_amount' => $variant->price_amount * $quantity,
        ]);

        StockReservation::create([
            'product_variant_id' => $variant->id,
            'order_id' => $order->id,
            'quantity' => $quantity,
            'status' => $reservationStatus,
            'expires_at' => now()->addMinutes(20),
        ]);

        return [$order, $inventory->fresh()];
    }
}
