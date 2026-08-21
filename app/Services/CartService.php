<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartService
{
    public function itemCount(Request $request): int
    {
        $query = Cart::query();

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } elseif ($token = $request->session()->get('lunar_cart_token')) {
            $query->where('session_token', $token);
        } else {
            // A fresh browser does not have a cart token yet. Never query
            // session_token = NULL because authenticated carts use that value.
            return 0;
        }

        return (int) ($query->withCount('items')->value('items_count') ?? 0);
    }

    public function current(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['currency' => 'VND']
            );
        }

        $token = $request->session()->get('lunar_cart_token', (string) Str::uuid());
        $request->session()->put('lunar_cart_token', $token);

        return Cart::firstOrCreate(
            ['session_token' => $token],
            ['currency' => 'VND']
        );
    }

    /**
     * Khi $shippingAmount = null, trang cart chỉ hiển thị ước tính cũ.
     * Checkout luôn truyền phí đã được ShippingFeeService tính ở backend.
     */
    public function totals(Cart $cart, ?int $shippingAmount = null, int $discountAmount = 0): array
    {
        $subtotal = (int) $cart->items->sum(
            fn ($item) => (int) $item->unit_price_amount * (int) $item->quantity
        );

        $shipping = $shippingAmount === null
            ? ($subtotal >= 5_000_000 || $subtotal === 0 ? 0 : 30_000)
            : max(0, $shippingAmount);

        $discount = min($subtotal, max(0, $discountAmount));

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount) + $shipping,
        ];
    }
}
