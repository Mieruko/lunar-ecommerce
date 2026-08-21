<?php
namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $carts, private CouponService $coupons) {}

    public function index(Request $request)
    {
        $cart = $this->carts->current($request);
        $cart->load(['items.variant.product.images', 'items.variant.inventory']);
        $coupon = null;
        $code = $this->coupons->normalize(session('checkout.coupon_code'));
        if ($code && ! $cart->items->isEmpty()) {
            try { $coupon = $this->coupons->preview($code, (int) $cart->items->sum(fn ($item) => $item->unit_price_amount * $item->quantity)); } catch (\Throwable) { session()->forget('checkout.coupon_code'); }
        }
        return view('store.cart', ['cart' => $cart, 'totals' => $this->carts->totals($cart, null, $coupon['discount'] ?? 0), 'coupon' => $coupon]);
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate(['variant_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1', 'max:10']]);
        $variant = ProductVariant::where('product_id', $product->id)->where('status', 'active')->findOrFail($data['variant_id']);
        if ($variant->availableStock() < $data['quantity']) return back()->withErrors(['stock' => 'Số lượng bạn chọn vượt quá tồn kho hiện có.']);
        $cart = $this->carts->current($request);
        $item = CartItem::firstOrNew(['cart_id' => $cart->id, 'product_variant_id' => $variant->id]);
        $item->quantity = min(10, $item->exists ? $item->quantity + $data['quantity'] : $data['quantity']);
        $item->unit_price_amount = $variant->price_amount; $item->save();
        return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $cart = $this->carts->current($request); abort_unless($cartItem->cart_id === $cart->id, 403);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10']]);
        if ($cartItem->variant->availableStock() < $data['quantity']) return back()->withErrors(['stock' => 'Sản phẩm không còn đủ tồn kho.']);
        $cartItem->update(['quantity' => $data['quantity']]); return back();
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $cart = $this->carts->current($request); abort_unless($cartItem->cart_id === $cart->id, 403);
        $cartItem->delete(); return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80']]);
        $cart = $this->carts->current($request);
        $cart->load('items');
        $this->coupons->preview($data['code'], (int) $cart->items->sum(fn ($item) => $item->unit_price_amount * $item->quantity));
        session(['checkout.coupon_code' => $this->coupons->normalize($data['code'])]);
        return back()->with('success', 'Đã áp dụng mã ưu đãi.');
    }

    public function removeCoupon()
    {
        session()->forget('checkout.coupon_code');
        return back()->with('success', 'Đã gỡ mã ưu đãi.');
    }
}
