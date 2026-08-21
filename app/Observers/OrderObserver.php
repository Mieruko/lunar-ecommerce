<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\CustomerNotification;

class OrderObserver
{
    private const STATUS_MESSAGES = [
        'pending_confirmation' => ['Đơn hàng đang chờ xác nhận', 'Lunar Jewels đã tiếp nhận đơn và đang kiểm tra thông tin.'],
        'confirmed' => ['Đơn hàng đã được xác nhận', 'Đơn hàng đã được xác nhận và sẽ sớm được chuẩn bị.'],
        'preparing' => ['Đơn hàng đang được chuẩn bị', 'Sản phẩm đang được kiểm tra và đóng gói cẩn thận.'],
        'shipping' => ['Đơn hàng đang trên đường giao', 'Đơn hàng đã được bàn giao cho đơn vị vận chuyển.'],
        'completed' => ['Đơn hàng đã giao thành công', 'Cảm ơn bạn đã lựa chọn Lunar Jewels.'],
        'cancelled' => ['Đơn hàng đã bị hủy', 'Đơn hàng đã được hủy. Hãy liên hệ hỗ trợ nếu bạn cần thêm thông tin.'],
        'returned' => ['Đơn hàng đã được trả lại', 'Quy trình đổi trả của đơn hàng đã hoàn tất.'],
    ];

    private const PAYMENT_MESSAGES = [
        'unpaid' => ['Đơn hàng chưa thanh toán', 'Khoản thanh toán của đơn hàng chưa được ghi nhận.'],
        'pending' => ['Thanh toán đang được xử lý', 'Lunar Jewels đang chờ kết quả từ phương thức thanh toán.'],
        'paid' => ['Thanh toán thành công', 'Khoản thanh toán đã được ghi nhận và đơn hàng đã được xác nhận.'],
        'partially_refunded' => ['Đã hoàn một phần tiền', 'Một phần giá trị thanh toán của đơn hàng đã được hoàn.'],
        'refunded' => ['Đã hoàn tiền', 'Khoản hoàn tiền của đơn hàng đã được ghi nhận.'],
        'failed' => ['Thanh toán chưa thành công', 'Giao dịch chưa hoàn tất. Bạn có thể kiểm tra lại trong chi tiết đơn hàng.'],
    ];

    public function created(Order $order): void
    {
        [$title, $message] = self::STATUS_MESSAGES[$order->status]
            ?? ['Đã tiếp nhận đơn hàng', 'Đơn hàng của bạn đã được tạo thành công.'];

        $this->notify($order, $title, $message, 'order');
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged(['status', 'payment_status'])) {
            return;
        }

        // A successful online payment normally confirms the order in the same
        // update. One concise notification is clearer than two duplicate alerts.
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
            [$title, $message] = self::PAYMENT_MESSAGES['paid'];
            $this->notify($order, $title, $message, 'payment');
            return;
        }

        if ($order->wasChanged('status')) {
            [$title, $message] = self::STATUS_MESSAGES[$order->status]
                ?? ['Trạng thái đơn hàng đã thay đổi', 'Đơn hàng vừa được cập nhật.'];
            $this->notify($order, $title, $message, 'order');
            return;
        }

        [$title, $message] = self::PAYMENT_MESSAGES[$order->payment_status]
            ?? ['Thanh toán đã được cập nhật', 'Thông tin thanh toán của đơn hàng vừa thay đổi.'];
        $this->notify($order, $title, $message, 'payment');
    }

    private function notify(Order $order, string $title, string $message, string $category): void
    {
        $customer = $order->customer()->first();
        if (! $customer) {
            return;
        }

        $customer->notify(new CustomerNotification(
            $category,
            $title,
            $message.' Mã đơn '.$order->order_number.'.',
            route('account.orders.show', $order, false),
            ['order_id' => $order->id, 'order_number' => $order->order_number],
        ));
    }
}
