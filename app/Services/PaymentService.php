<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\OrderStatusHistory;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private StockService $stock,
        private AdminActivityLogger $activity,
        private CouponService $coupons,
    ) {}

    public function markPaid(Payment $payment, ?User $actor = null, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $payload) {
            $locked = Payment::query()->with('order.items')->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status === 'paid') return $locked;

            $before = $locked->only(['status', 'paid_at']);
            $locked->update(['status' => 'paid', 'paid_at' => now(), 'provider_payload' => $payload ?: $locked->provider_payload]);
            $order = $locked->order;
            $wasPendingConfirmation = $order->status === 'pending_confirmation';
            $order->update([
                'payment_status' => 'paid',
                'status' => $wasPendingConfirmation ? 'confirmed' : $order->status,
            ]);
            if ($wasPendingConfirmation) {
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'confirmed',
                    'comment' => 'Thanh toán đã được xác nhận.',
                    'changed_by' => $actor?->id,
                ]);
            }
            $this->stock->convert($order);
            $this->activity->log('payment.mark_paid', $locked, $before, $locked->fresh()->only(['status', 'paid_at']));

            return $locked->fresh();
        });
    }

    public function markFailed(Payment $payment, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $payload) {
            $locked = Payment::query()->with('order')->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status === 'paid') return $locked;
            if ($locked->status === 'failed') return $locked;
            $locked->update(['status' => 'failed', 'provider_payload' => $payload ?: $locked->provider_payload]);
            $locked->order->update(['payment_status' => 'failed']);
            $this->stock->release($locked->order);
            $this->coupons->releaseForOrder($locked->order);
            return $locked->fresh();
        });
    }

    public function recordRefund(Payment $payment, int $amount, string $reason, ?User $actor = null): Refund
    {
        return DB::transaction(function () use ($payment, $amount, $reason, $actor) {
            $locked = Payment::query()->with(['order', 'refunds'])->lockForUpdate()->findOrFail($payment->id);
            $alreadyRefunded = (int) $locked->refunds->sum('amount');
            if ($amount < 1 || $alreadyRefunded + $amount > (int) $locked->amount) {
                throw ValidationException::withMessages(['amount' => 'Số tiền hoàn không hợp lệ hoặc vượt giá trị giao dịch.']);
            }

            $refund = Refund::create([
                'order_id' => $locked->order_id,
                'payment_id' => $locked->id,
                'amount' => $amount,
                'reason' => $reason,
                'recorded_by' => $actor?->id,
                'recorded_at' => now(),
            ]);
            $total = $alreadyRefunded + $amount;
            $locked->update(['status' => $total === (int) $locked->amount ? 'refunded' : $locked->status]);
            $locked->order->update(['payment_status' => $total === (int) $locked->amount ? 'refunded' : 'partially_refunded']);
            $this->activity->log('payment.refund_recorded', $refund, null, $refund->only(['amount', 'reason', 'payment_id']));

            return $refund;
        });
    }
}
