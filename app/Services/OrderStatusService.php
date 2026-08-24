<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function __construct(private StockService $stock) {}

    private const TRANSITIONS = [
        'pending_confirmation' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['shipping', 'cancelled'],
        'shipping' => ['completed'],
        'completed' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ];

    public function nextStatuses(Order $order): array
    {
        return self::TRANSITIONS[$order->status] ?? [];
    }

    public function transition(Order $order, string $targetStatus, ?User $actor = null, ?string $comment = null): Order
    {
        return DB::transaction(function () use ($order, $targetStatus, $actor, $comment) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $allowed = self::TRANSITIONS[$lockedOrder->status] ?? [];

            if (! in_array($targetStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => "Không thể chuyển đơn từ {$lockedOrder->status} sang {$targetStatus}.",
                ]);
            }

            if ($targetStatus === 'cancelled' && blank($comment)) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Vui lòng nhập lý do hủy đơn.',
                ]);
            }

            if ($targetStatus === 'shipping' && ! $lockedOrder->shipments()
                ->where('status', 'shipped')
                ->whereNotNull('carrier')
                ->whereNotNull('tracking_number')
                ->exists()) {
                throw ValidationException::withMessages(['status' => 'Cần có vận đơn với đơn vị vận chuyển và mã tracking trước khi bàn giao đơn.']);
            }

            if ($targetStatus === 'completed') {
                $hasDeliveredShipment = $lockedOrder->shipments()->where('status', 'delivered')->exists();
                $hasUndeliveredShipment = $lockedOrder->shipments()->where('status', '!=', 'delivered')->exists();
                $hasUnpaidManualPayment = $lockedOrder->payments()
                    ->whereIn('provider', ['cod', 'bank_transfer'])
                    ->where('status', '!=', 'paid')
                    ->exists();
                if (! $hasDeliveredShipment || $hasUndeliveredShipment || $hasUnpaidManualPayment) {
                    throw ValidationException::withMessages(['status' => 'Chỉ hoàn tất khi vận đơn đã giao thành công và các khoản thanh toán thủ công đã được xác nhận.']);
                }
            }

            $changes = ['status' => $targetStatus];
            if ($targetStatus === 'cancelled') {
                $changes['cancellation_reason'] = trim((string) $comment);
                $changes['cancelled_at'] = now();
            }
            $lockedOrder->update($changes);

            if ($targetStatus === 'cancelled') {
                $this->stock->restoreForCancellation($lockedOrder);
                $lockedOrder->shipments()
                    ->whereNotIn('status', ['delivered', 'returned'])
                    ->update(['status' => 'cancelled', 'updated_at' => now()]);
            }

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => $targetStatus,
                'comment' => $comment,
                'changed_by' => $actor?->id,
            ]);

            return $lockedOrder->refresh();
        });
    }
}
