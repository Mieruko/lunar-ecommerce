<?php

namespace App\Observers;

use App\Models\WarrantyClaim;
use App\Notifications\CustomerNotification;

class WarrantyClaimObserver
{
    private const STATUS_LABELS = [
        'submitted' => 'Đã tiếp nhận yêu cầu bảo hành',
        'approved' => 'Yêu cầu bảo hành đã được tiếp nhận xử lý',
        'in_repair' => 'Sản phẩm đang được bảo hành',
        'resolved' => 'Yêu cầu bảo hành đã hoàn tất',
        'rejected' => 'Yêu cầu bảo hành chưa được chấp nhận',
    ];

    public function created(WarrantyClaim $claim): void
    {
        $this->notify($claim);
    }

    public function updated(WarrantyClaim $claim): void
    {
        if ($claim->wasChanged(['status', 'resolution'])) {
            $this->notify($claim);
        }
    }

    private function notify(WarrantyClaim $claim): void
    {
        $claim->loadMissing('warranty.orderItem.order.customer');
        $order = $claim->warranty?->orderItem?->order;
        if (! $order?->customer) {
            return;
        }

        $message = 'Phiếu '.$claim->claim_number.' · đơn '.$order->order_number.'.';
        if ($claim->status === 'resolved' && filled($claim->resolution)) {
            $message .= ' Kết quả: '.$claim->resolution;
        }

        $order->customer->notify(new CustomerNotification(
            'warranty',
            self::STATUS_LABELS[$claim->status] ?? 'Yêu cầu bảo hành đã được cập nhật',
            $message,
            route('account.after-sales', [], false),
            ['order_id' => $order->id, 'warranty_claim_id' => $claim->id],
        ));
    }
}
