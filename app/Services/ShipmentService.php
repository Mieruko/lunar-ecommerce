<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    private const TRANSITIONS = [
        'pending' => ['packed'],
        'packed' => ['shipped'],
        'shipped' => ['delivered', 'failed'],
        'failed' => ['shipped', 'returned'],
        'delivered' => [],
        'returned' => [],
        'cancelled' => [],
    ];

    public function __construct(private OrderStatusService $orders, private AdminActivityLogger $activity) {}

    public function createForOrder(Order $order, string $carrier, string $trackingNumber): Shipment
    {
        return DB::transaction(function () use ($order, $carrier, $trackingNumber): Shipment {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $carrier = trim($carrier);
            $trackingNumber = trim($trackingNumber);

            if ($lockedOrder->status !== 'preparing') {
                throw ValidationException::withMessages([
                    'order_id' => 'Chỉ tạo vận đơn cho đơn đang ở trạng thái Chuẩn bị hàng.',
                ]);
            }

            if ($lockedOrder->shipments()->exists()) {
                throw ValidationException::withMessages([
                    'order_id' => 'Đơn hàng này đã có vận đơn.',
                ]);
            }

            if (blank($carrier) || blank($trackingNumber)) {
                throw ValidationException::withMessages([
                    'tracking_number' => 'Vui lòng nhập đơn vị vận chuyển và mã vận đơn thực tế.',
                ]);
            }

            if (Shipment::query()->where('carrier', $carrier)->where('tracking_number', $trackingNumber)->exists()) {
                throw ValidationException::withMessages([
                    'tracking_number' => 'Mã vận đơn này đã được sử dụng cho cùng đơn vị vận chuyển.',
                ]);
            }

            $shipment = $lockedOrder->shipments()->create([
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber,
                'status' => 'packed',
            ]);

            $this->activity->log('shipment.created', $shipment, null, $shipment->only([
                'order_id',
                'carrier',
                'tracking_number',
                'status',
            ]));

            return $shipment;
        });
    }

    /** @return list<string> */
    public function nextStatuses(Shipment $shipment): array
    {
        return self::TRANSITIONS[$shipment->status] ?? [];
    }

    public function updateStatus(Shipment $shipment, string $status): Shipment
    {
        return DB::transaction(function () use ($shipment, $status) {
            $locked = Shipment::query()->with('order')->lockForUpdate()->findOrFail($shipment->id);
            if (in_array($locked->order->status, ['cancelled', 'returned'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể cập nhật vận chuyển cho đơn đã hủy hoặc đã trả hàng.',
                ]);
            }

            if (! in_array($status, self::TRANSITIONS[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Không thể chuyển vận đơn từ {$locked->status} sang {$status}.",
                ]);
            }
            if (in_array($status, ['shipped', 'delivered'], true) && (! $locked->carrier || ! $locked->tracking_number)) {
                throw ValidationException::withMessages(['tracking_number' => 'Cần nhập đơn vị vận chuyển và mã tracking trước khi cập nhật trạng thái giao hàng.']);
            }

            if (in_array($status, ['shipped', 'delivered'], true) && $locked->order->payments()
                ->where('provider', 'bank_transfer')
                ->where('status', '!=', 'paid')
                ->exists()) {
                throw ValidationException::withMessages(['payment' => 'Cần đối soát và xác nhận tiền chuyển khoản trước khi bàn giao đơn.']);
            }

            if ($status === 'delivered' && $locked->order->payments()
                ->whereIn('provider', ['cod', 'bank_transfer'])
                ->where('status', '!=', 'paid')
                ->exists()) {
                throw ValidationException::withMessages(['payment' => 'Cần xác nhận đã thu tiền trước khi đánh dấu giao thành công.']);
            }
            $before = $locked->only(['status', 'shipped_at', 'delivered_at']);
            $changes = ['status' => $status];
            if ($status === 'shipped' && ! $locked->shipped_at) {
                $changes['shipped_at'] = now();
            }
            if ($status === 'delivered' && ! $locked->delivered_at) {
                $changes['delivered_at'] = now();
            }
            $locked->update($changes);

            $order = $locked->order;
            if ($status === 'shipped' && in_array('shipping', $this->orders->nextStatuses($order), true)) {
                $this->orders->transition($order, 'shipping', auth()->user(), 'Đã bàn giao vận chuyển.');
                $order->update(['fulfillment_status' => 'fulfilled']);
            }
            if ($status === 'delivered' && in_array('completed', $this->orders->nextStatuses($order), true)) {
                $this->orders->transition($order, 'completed', auth()->user(), 'Đơn đã giao thành công.');
            }
            if ($status === 'returned') {
                $order->update(['fulfillment_status' => 'returned']);
            }

            $this->activity->log('shipment.status_changed', $locked, $before, $locked->fresh()->only(['status', 'shipped_at', 'delivered_at']));

            return $locked->fresh();
        });
    }
}
