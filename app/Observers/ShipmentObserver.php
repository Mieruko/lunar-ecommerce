<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Notifications\CustomerNotification;

class ShipmentObserver
{
    private const STATUS_MESSAGES = [
        'packed' => ['Đơn hàng đã đóng gói', 'Sản phẩm đã được kiểm tra và đóng gói, sẵn sàng bàn giao vận chuyển.'],
        'failed' => ['Giao hàng chưa thành công', 'Đơn vị vận chuyển chưa thể giao hàng. Lunar Jewels sẽ tiếp tục hỗ trợ bạn.'],
        'returned' => ['Vận đơn đang hoàn về', 'Đơn hàng đang được đơn vị vận chuyển hoàn lại Lunar Jewels.'],
        'cancelled' => ['Vận đơn đã hủy', 'Vận đơn đã được đóng do đơn hàng bị hủy.'],
    ];

    public function updated(Shipment $shipment): void
    {
        $order = $shipment->order()->with('customer')->first();
        if (! $order?->customer) {
            return;
        }

        $translationKey = null;
        $translationParams = ['order' => $order->order_number];
        if ($shipment->wasChanged('status') && isset(self::STATUS_MESSAGES[$shipment->status])) {
            [$title, $message] = self::STATUS_MESSAGES[$shipment->status];
            $translationKey = 'notifications.content.shipment.'.$shipment->status;
        } elseif ($shipment->wasChanged('tracking_number') && filled($shipment->tracking_number)) {
            $title = 'Đã có mã vận đơn';
            $message = trim(($shipment->carrier ?: 'Đơn vị vận chuyển').' · '.$shipment->tracking_number);
            $translationKey = 'notifications.content.shipment.tracking_added';
            $translationParams['carrier'] = $shipment->carrier ?: 'Đơn vị vận chuyển';
            $translationParams['tracking'] = $shipment->tracking_number;
        } else {
            return;
        }

        $order->customer->notify(new CustomerNotification(
            'shipment',
            $title,
            $message.' Mã đơn '.$order->order_number.'.',
            route('account.orders.show', $order, false),
            ['order_id' => $order->id, 'shipment_id' => $shipment->id],
            $translationKey,
            $translationParams,
        ));
    }
}
