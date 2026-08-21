<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(private OrderStatusService $orders, private AdminActivityLogger $activity) {}

    public function updateStatus(Shipment $shipment, string $status): Shipment
    {
        return DB::transaction(function () use ($shipment, $status) {
            $locked = Shipment::query()->with('order')->lockForUpdate()->findOrFail($shipment->id);
            if (in_array($status, ['shipped', 'delivered'], true) && (! $locked->carrier || ! $locked->tracking_number)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['tracking_number' => 'Cần nhập đơn vị vận chuyển và mã tracking trước khi cập nhật trạng thái giao hàng.']);
            }

            if (in_array($status, ['shipped', 'delivered'], true) && $locked->order->payments()
                ->where('provider', 'bank_transfer')
                ->where('status', '!=', 'paid')
                ->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Cần đối soát và xác nhận tiền chuyển khoản trước khi bàn giao đơn.']);
            }

            if ($status === 'delivered' && $locked->order->payments()
                ->whereIn('provider', ['cod', 'bank_transfer'])
                ->where('status', '!=', 'paid')
                ->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Cần xác nhận đã thu tiền trước khi đánh dấu giao thành công.']);
            }
            $before = $locked->only(['status', 'shipped_at', 'delivered_at']);
            $changes = ['status' => $status];
            if ($status === 'shipped' && ! $locked->shipped_at) $changes['shipped_at'] = now();
            if ($status === 'delivered' && ! $locked->delivered_at) $changes['delivered_at'] = now();
            $locked->update($changes);

            $order = $locked->order;
            if ($status === 'shipped' && in_array('shipping', $this->orders->nextStatuses($order), true)) {
                $this->orders->transition($order, 'shipping', auth()->user(), 'Đã bàn giao vận chuyển.');
                $order->update(['fulfillment_status' => 'fulfilled']);
            }
            if ($status === 'delivered' && in_array('completed', $this->orders->nextStatuses($order), true)) {
                $this->orders->transition($order, 'completed', auth()->user(), 'Đơn đã giao thành công.');
            }
            if ($status === 'returned') $order->update(['fulfillment_status' => 'returned']);

            $this->activity->log('shipment.status_changed', $locked, $before, $locked->fresh()->only(['status', 'shipped_at', 'delivered_at']));
            return $locked->fresh();
        });
    }
}
