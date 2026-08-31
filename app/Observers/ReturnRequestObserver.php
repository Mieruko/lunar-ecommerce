<?php

namespace App\Observers;

use App\Models\ReturnRequest;
use App\Notifications\CustomerNotification;

class ReturnRequestObserver
{
    private const STATUS_LABELS = [
        'requested' => 'Đã tiếp nhận yêu cầu đổi trả',
        'approved' => 'Yêu cầu đổi trả đã được chấp nhận',
        'rejected' => 'Yêu cầu đổi trả chưa được chấp nhận',
        'received' => 'Lunar Jewels đã nhận sản phẩm đổi trả',
        'refunded' => 'Yêu cầu đổi trả đã được hoàn tiền',
        'closed' => 'Yêu cầu đổi trả đã đóng',
    ];

    public function created(ReturnRequest $request): void
    {
        $this->notify($request);
    }

    public function updated(ReturnRequest $request): void
    {
        if ($request->wasChanged('status')) {
            $this->notify($request);
        }
    }

    private function notify(ReturnRequest $request): void
    {
        $order = $request->order()->with('customer')->first();
        if (! $order?->customer) {
            return;
        }

        $order->customer->notify(new CustomerNotification(
            'return',
            self::STATUS_LABELS[$request->status] ?? 'Yêu cầu đổi trả đã được cập nhật',
            'Phiếu '.$request->return_number.' · đơn '.$order->order_number.'.',
            route('account.after-sales', [], false),
            ['order_id' => $order->id, 'return_request_id' => $request->id],
            'notifications.content.return.'.($request->status ?: 'generic'),
            ['return' => $request->return_number, 'order' => $order->order_number],
        ));
    }
}
