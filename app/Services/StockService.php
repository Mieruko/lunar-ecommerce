<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function reserve(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = DB::table('inventory')->where('product_variant_id', $item->product_variant_id)->lockForUpdate()->first();
            if (! $inventory || ($inventory->quantity_on_hand - $inventory->quantity_reserved) < $item->quantity) {
                abort(422, 'Sản phẩm vừa hết hàng.');
            }
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

    /**
     * Return every stock allocation owned by a cancelled order.
     *
     * Active reservations have not reduced on-hand stock yet, so they only
     * need to be released. Converted reservations represent completed stock
     * movements and must be added back to on-hand stock. The order-level
     * stock_reverted_at flag makes the whole operation idempotent.
     */
    public function restoreForCancellation(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->stock_reverted_at) {
                return;
            }

            $reservations = StockReservation::query()
                ->where('order_id', $lockedOrder->id)
                ->whereIn('status', ['active', 'converted'])
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $inventory = DB::table('inventory')
                    ->where('product_variant_id', $reservation->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory) {
                    continue;
                }

                if ($reservation->status === 'active') {
                    DB::table('inventory')->where('id', $inventory->id)->update([
                        'quantity_reserved' => max(0, (int) $inventory->quantity_reserved - $reservation->quantity),
                        'updated_at' => now(),
                    ]);
                    InventoryTransaction::create([
                        'warehouse_id' => $inventory->warehouse_id,
                        'product_variant_id' => $reservation->product_variant_id,
                        'transaction_type' => 'release',
                        'quantity_delta' => 0,
                        'reference_type' => Order::class,
                        'reference_id' => $lockedOrder->id,
                        'notes' => 'Giải phóng giữ chỗ do hủy đơn.',
                    ]);
                    $reservation->update(['status' => 'released']);

                    continue;
                }

                DB::table('inventory')->where('id', $inventory->id)->update([
                    'quantity_on_hand' => (int) $inventory->quantity_on_hand + $reservation->quantity,
                    'updated_at' => now(),
                ]);
                InventoryTransaction::create([
                    'warehouse_id' => $inventory->warehouse_id,
                    'product_variant_id' => $reservation->product_variant_id,
                    'transaction_type' => 'return',
                    'quantity_delta' => $reservation->quantity,
                    'reference_type' => Order::class,
                    'reference_id' => $lockedOrder->id,
                    'notes' => 'Hoàn tồn kho do hủy đơn.',
                ]);
            }

            $lockedOrder->update(['stock_reverted_at' => now()]);
        });
    }
}
