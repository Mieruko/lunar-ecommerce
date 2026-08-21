<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\StockService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:expire-pending', function () {
    $expired = 0;

    Order::query()
        ->where('status', 'cancelled')
        ->whereIn('id', \App\Models\StockReservation::query()
            ->select('order_id')
            ->where('status', 'active')
            ->whereNotNull('order_id'))
        ->with('payments')
        ->each(function (Order $order) use (&$expired) {
            app(StockService::class)->release($order);
            $order->payments()->where('status', 'pending')->update(['status' => 'cancelled']);
            if ($order->payment_status === 'pending') $order->update(['payment_status' => 'failed']);
            $expired++;
        });

    Order::query()
        ->where('status', 'pending_confirmation')
        ->whereHas('payments', fn ($query) => $query
            ->whereIn('provider', ['paypal', 'vnpay'])
            ->where('status', 'pending'))
        ->whereIn('id', \App\Models\StockReservation::query()
            ->select('order_id')
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->whereNotNull('order_id'))
        ->with('payments')
        ->chunkById(100, function ($orders) use (&$expired) {
            foreach ($orders as $order) {
                $payment = $order->payments
                    ->whereIn('provider', ['paypal', 'vnpay'])
                    ->firstWhere('status', 'pending');
                if (! $payment) continue;

                app(PaymentService::class)->markFailed($payment, [
                    'stage' => 'reservation_expired',
                    'message' => 'Hết thời gian chờ thanh toán.',
                ]);
                $order->update(['status' => 'cancelled']);
                $expired++;
            }
        });

    $this->info("Đã hết hạn {$expired} đơn thanh toán online.");
})->purpose('Hủy đơn PayPal/VNPAY quá hạn và giải phóng tồn kho.');

Schedule::command('payments:expire-pending')->everyMinute()->withoutOverlapping();
