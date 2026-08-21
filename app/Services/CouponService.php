<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function normalize(?string $code): ?string
    {
        $code = Str::upper(trim((string) $code));

        return $code === '' ? null : $code;
    }

    /** @return array{coupon: Coupon, code: string, discount: int} */
    public function preview(string $code, int $subtotal, ?string $email = null): array
    {
        $coupon = Coupon::query()->where('code', $this->normalize($code))->first();

        return $this->validate($coupon, $subtotal, $email);
    }

    /** Call from the order transaction: serializes redemptions for a coupon. */
    public function redeem(string $code, int $subtotal, ?string $email, ?User $user, Order $order): array
    {
        $coupon = Coupon::query()->where('code', $this->normalize($code))->lockForUpdate()->first();
        $quote = $this->validate($coupon, $subtotal, $email);

        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'user_id' => $user?->id,
            'customer_email' => $this->email($email),
            'discount_amount' => $quote['discount'],
        ]);

        $coupon->increment('usage_count');

        return $quote;
    }

    public function releaseForOrder(Order $order): void
    {
        $redemption = CouponRedemption::query()
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (! $redemption) return;

        $coupon = Coupon::query()->lockForUpdate()->find($redemption->coupon_id);
        $redemption->delete();

        if ($coupon && (int) $coupon->usage_count > 0) {
            $coupon->decrement('usage_count');
        }
    }

    private function validate(?Coupon $coupon, int $subtotal, ?string $email): array
    {
        $message = match (true) {
            ! $coupon => 'Mã ưu đãi không tồn tại.',
            ! $coupon->is_active => 'Mã ưu đãi hiện không hoạt động.',
            $coupon->starts_at && $coupon->starts_at->isFuture() => 'Mã ưu đãi chưa đến thời gian sử dụng.',
            $coupon->ends_at && $coupon->ends_at->isPast() => 'Mã ưu đãi đã hết hạn.',
            $subtotal < (int) $coupon->minimum_order_amount => 'Đơn hàng chưa đạt giá trị tối thiểu để dùng mã này.',
            $coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit => 'Mã ưu đãi đã hết lượt sử dụng.',
            $coupon->per_customer_limit !== null && $email && CouponRedemption::query()
                ->where('coupon_id', $coupon->id)
                ->where('customer_email', $this->email($email))
                ->count() >= $coupon->per_customer_limit => 'Bạn đã dùng hết lượt của mã ưu đãi này.',
            $coupon->discount_type === 'percent' && ((int) $coupon->discount_value < 1 || (int) $coupon->discount_value > 100) => 'Cấu hình phần trăm giảm giá không hợp lệ.',
            $coupon->discount_type === 'fixed' && (int) $coupon->discount_value < 1 => 'Cấu hình giá trị giảm giá không hợp lệ.',
            default => null,
        };

        if ($message) {
            throw ValidationException::withMessages(['coupon' => $message]);
        }

        $discount = $coupon->discount_type === 'percent'
            ? (int) floor($subtotal * ((int) $coupon->discount_value / 100))
            : (int) $coupon->discount_value;

        return ['coupon' => $coupon, 'code' => $coupon->code, 'discount' => min($subtotal, $discount)];
    }

    private function email(?string $email): ?string
    {
        return $email ? Str::lower(trim($email)) : null;
    }
}
