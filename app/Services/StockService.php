<?php
namespace App\Services;

use App\Models\Order;
use App\Models\StockReservation;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function reserve(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = DB::table('inventory')->where('product_variant_id', $item->product_variant_id)->lockForUpdate()->first();
            if (! $inventory || ($inventory->quantity_on_hand - $inventory->quantity_reserved) < $item->quantity) abort(422, 'Sản phẩm vừa hết hàng.');
            DB::table('inventory')->where('id', $inventory->id)->increment('quantity_reserved', $item->quantity);
            StockReservation::create(['product_variant_id' => $item->product_variant_id, 'order_id' => $order->id, 'quantity' => $item->quantity, 'status' => 'active', 'expires_at' => now()->addMinutes(20)]);
        }
    }

    public function convert(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach (StockReservation::where('order_id', $order->id)->where('status', 'active')->lockForUpdate()->get() as $reservation) {
                $inventory = DB::table('inventory')->where('product_variant_id', $reservation->product_variant_id)->lockForUpdate()->first();
                DB::table('inventory')->where('id', $inventory->id)->update(['quantity_on_hand' => $inventory->quantity_on_hand - $reservation->quantity, 'quantity_reserved' => $inventory->quantity_reserved - $reservation->quantity, 'updated_at' => now()]);
                InventoryTransaction::create(['warehouse_id' => $inventory->warehouse_id, 'product_variant_id' => $reservation->product_variant_id, 'transaction_type' => 'sale', 'quantity_delta' => -$reservation->quantity, 'reference_type' => Order::class, 'reference_id' => $order->id, 'notes' => 'Xuất kho theo đơn hàng.']);
                $reservation->update(['status' => 'converted']);
            }
        });
    }

    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach (StockReservation::where('order_id', $order->id)->where('status', 'active')->lockForUpdate()->get() as $reservation) {
                $inventory = DB::table('inventory')->where('product_variant_id', $reservation->product_variant_id)->lockForUpdate()->first();
                DB::table('inventory')->where('id', $inventory->id)->decrement('quantity_reserved', $reservation->quantity);
                InventoryTransaction::create(['warehouse_id' => $inventory->warehouse_id, 'product_variant_id' => $reservation->product_variant_id, 'transaction_type' => 'release', 'quantity_delta' => 0, 'reference_type' => Order::class, 'reference_id' => $order->id, 'notes' => 'Giải phóng tồn giữ chỗ.']);
                $reservation->update(['status' => 'released']);
            }
        });
    }
}
