<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\ShippingFeeService;
use App\Services\StockService;
use App\Services\VietnamAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $carts,
        private StockService $stock,
        private VietnamAddressService $addresses,
        private ShippingFeeService $shippingFees,
        private CouponService $coupons,
    ) {}

    public function shipping(Request $request)
    {
        $cart = $this->carts->current($request);
        $cart->load(['items.variant.product.images', 'items.variant.inventory']);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $shipping = session('checkout.shipping', []);
        if (! $shipping && $request->user()) {
            $address = $request->user()->addresses()->where('is_default_shipping', true)->first();
            if ($address?->province_code && $address?->ward_code) {
                $shipping = ['recipient_name' => $address->recipient_name, 'email' => $request->user()->email, 'phone' => $address->phone, 'line_1' => $address->line_1, 'province_code' => $address->province_code, 'province' => $address->province, 'ward_code' => $address->ward_code, 'ward' => $address->ward, 'country_code' => 'VN'];
            }
        }
        $quote = session('checkout.shipping_quote');
        $shippingAmount = is_array($quote) ? (int) ($quote['shipping_fee'] ?? 0) : 0;
        $coupon = $this->couponPreview($cart);

        return view('store.checkout-shipping', [
            'cart' => $cart,
            'totals' => $this->carts->totals($cart, $shippingAmount, $coupon['discount'] ?? 0),
            'shipping' => $shipping,
            'shippingQuote' => $quote,
            'coupon' => $coupon,
        ]);
    }

    public function provinces(): JsonResponse
    {
        return response()->json([
            'data' => $this->addresses->provinces()->map(fn ($province) => [
                'code' => $province->code,
                'name' => $province->full_name,
            ])->values(),
        ]);
    }

    public function wards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_code' => ['required', 'string', 'size:2'],
        ]);

        return response()->json([
            'data' => $this->addresses
                ->wards($validated['province_code'])
                ->map(fn ($ward) => [
                    'code' => $ward->code,
                    'name' => $ward->full_name,
                ])
                ->values(),
        ]);
    }

    public function quoteShipping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_code' => ['required', 'string', 'size:2'],
            'ward_code' => ['required', 'string', 'size:5'],
        ]);

        $cart = $this->carts->current($request);
        $cart->load('items');

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng đang trống.'], 422);
        }

        try {
            return response()->json(
                $this->shippingFees->quote(
                    $cart,
                    $validated['province_code'],
                    $validated['ward_code'],
                )
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeShipping(Request $request)
    {
        $data = $request->validate([
            'shipping.recipient_name' => ['required', 'string', 'max:150'],
            'shipping.email' => ['required', 'email', 'max:255'],
            'shipping.phone' => ['required', 'string', 'max:30'],
            'shipping.line_1' => ['required', 'string', 'max:255'],
            'shipping.province_code' => ['required', 'string', 'size:2'],
            'shipping.ward_code' => ['required', 'string', 'size:5'],
            'shipping.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $this->carts->current($request);
        $cart->load('items');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        try {
            $resolved = $this->addresses->resolve(
                $data['shipping']['province_code'],
                $data['shipping']['ward_code'],
            );

            // Tính lại ở server. Không nhận shipping_fee từ browser.
            $quote = $this->shippingFees->quote(
                $cart,
                $data['shipping']['province_code'],
                $data['shipping']['ward_code'],
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'shipping.ward_code' => 'Địa chỉ hoặc khu vực giao hàng chưa hợp lệ. Vui lòng chọn lại.',
                ]);
        }

        $shipping = [
            'recipient_name' => $data['shipping']['recipient_name'],
            'email' => $data['shipping']['email'],
            'phone' => $data['shipping']['phone'],
            'line_1' => $data['shipping']['line_1'],
            'province_code' => $resolved['province']->code,
            'province' => $resolved['province']->full_name,
            'ward_code' => $resolved['ward']->code,
            'ward' => $resolved['ward']->full_name,
            'district' => null,
            'country_code' => 'VN',
            'note' => $data['shipping']['note'] ?? null,
        ];

        try {
            $this->couponPreview($cart, $shipping['email']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        session([
            'checkout.shipping' => $shipping,
            'checkout.shipping_quote' => $quote,
        ]);

        return redirect()->route('checkout.payment');
    }

    public function payment(Request $request)
    {
        $shipping = session('checkout.shipping');
        $quote = session('checkout.shipping_quote');

        if (! $shipping || ! $quote) {
            return redirect()->route('checkout.shipping');
        }

        $cart = $this->carts->current($request);
        $cart->load(['items.variant.product.images', 'items.variant.inventory']);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $coupon = $this->couponPreview($cart, $shipping['email']);
        $totals = $this->carts->totals($cart, (int) $quote['shipping_fee'], $coupon['discount'] ?? 0);

        $rate = (int) (
            DB::table('store_settings')
                ->where('key', 'paypal_vnd_usd_rate')
                ->value('value') ?: 25000
        );

        return view('store.checkout-payment', [
            'cart' => $cart,
            'totals' => $totals,
            'shipping' => $shipping,
            'shippingQuote' => $quote,
            'coupon' => $coupon,
            'paypalUsd' => round($totals['total'] / $rate, 2),
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'shipping_method' => ['required', 'in:standard'],
            'payment_method' => ['required', 'in:bank_transfer,vnpay_debit,vnpay_credit,vnpay_qr,paypal,cod'],
        ]);

        // Validate availability before creating an order, redeeming a coupon, or reserving stock.
        if (! ($this->paymentMethods()[$data['payment_method']] ?? false)) {
            return redirect()
                ->route('checkout.payment')
                ->with('error', 'Phương thức thanh toán này hiện chưa sẵn sàng. Vui lòng chọn phương thức khác.');
        }

        $shipping = session('checkout.shipping');
        $storedQuote = session('checkout.shipping_quote');

        if (! $shipping || ! $storedQuote) {
            return redirect()->route('checkout.shipping');
        }

        $cart = $this->carts->current($request);
        $cart->load(['items.variant.product.images', 'items.variant.inventory']);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        try {
            // Quote lại ngay trước khi đặt hàng để tránh session cũ hoặc client can thiệp.
            $freshQuote = $this->shippingFees->quote(
                $cart,
                $shipping['province_code'],
                $shipping['ward_code'],
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('checkout.shipping')
                ->with('error', 'Không thể xác nhận phí vận chuyển. Vui lòng chọn lại địa chỉ.');
        }

        if ((int) $freshQuote['shipping_fee'] !== (int) ($storedQuote['shipping_fee'] ?? -1)) {
            session(['checkout.shipping_quote' => $freshQuote]);

            return redirect()
                ->route('checkout.payment')
                ->with('error', 'Phí vận chuyển vừa được cập nhật. Vui lòng kiểm tra lại tổng thanh toán.');
        }

        try {
            $order = DB::transaction(function () use ($request, $cart, $shipping, $data, $freshQuote) {
            $subtotal = (int) $cart->items->sum(fn ($item) => $item->unit_price_amount * $item->quantity);
            $couponCode = $this->coupons->normalize(session('checkout.coupon_code'));
            $coupon = $couponCode ? $this->coupons->preview($couponCode, $subtotal, $shipping['email']) : null;
            $totals = $this->carts->totals($cart, (int) $freshQuote['shipping_fee'], $coupon['discount'] ?? 0);

            $order = Order::create([
                'order_number' => 'LJ-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
                'user_id' => $request->user()?->id,
                'status' => $data['payment_method'] === 'cod' ? 'confirmed' : 'pending_confirmation',
                'payment_status' => $data['payment_method'] === 'cod' ? 'unpaid' : 'pending',
                'currency' => 'VND',
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'coupon_id' => $coupon['coupon']->id ?? null,
                'coupon_code' => $coupon['code'] ?? null,
                'shipping_amount' => $totals['shipping'],
                'tax_amount' => 0,
                'total_amount' => $totals['total'],
                'customer_name' => $shipping['recipient_name'],
                'customer_email' => $shipping['email'],
                'customer_phone' => $shipping['phone'],
                'note' => $shipping['note'] ?? null,
                'placed_at' => now(),
            ]);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'comment' => $order->status === 'confirmed'
                    ? 'Đơn COD đã được tiếp nhận và xác nhận.'
                    : 'Đơn hàng đã được tiếp nhận, đang chờ hoàn tất thanh toán.',
            ]);

            OrderAddress::create([
                'order_id' => $order->id,
                'address_type' => 'shipping',
                'recipient_name' => $shipping['recipient_name'],
                'phone' => $shipping['phone'],
                'line_1' => $shipping['line_1'],
                'ward' => $shipping['ward'],
                'ward_code' => $shipping['ward_code'],
                'district' => null,
                'province' => $shipping['province'],
                'province_code' => $shipping['province_code'],
                'shipping_zone_code' => $freshQuote['zone_code'],
                'country_code' => 'VN',
            ]);

            foreach ($cart->items as $item) {
                $image = $item->variant->product->images->first()?->path;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->variant->product_id,
                    'product_variant_id' => $item->variant->id,
                    'sku' => $item->variant->sku,
                    'product_name' => $item->variant->product->name,
                    'variant_name' => $item->variant->name,
                    'image_path' => $image,
                    'unit_price_amount' => $item->unit_price_amount,
                    'quantity' => $item->quantity,
                    'total_amount' => $item->unit_price_amount * $item->quantity,
                    'product_snapshot' => [
                        'name' => $item->variant->product->name,
                        'sku' => $item->variant->sku,
                    ],
                ]);
            }

            $provider = str_starts_with($data['payment_method'], 'vnpay')
                ? 'vnpay'
                : $data['payment_method'];

            $rate = (int) (
                DB::table('store_settings')
                    ->where('key', 'paypal_vnd_usd_rate')
                    ->value('value') ?: 25000
            );

            Payment::create([
                'order_id' => $order->id,
                'provider' => $provider,
                'payment_method' => $data['payment_method'],
                'amount' => $order->total_amount,
                'currency' => 'VND',
                'payment_currency' => $provider === 'paypal' ? 'USD' : 'VND',
                'provider_amount' => $provider === 'paypal'
                    ? round($order->total_amount / $rate, 2)
                    : $order->total_amount,
                'exchange_rate' => $provider === 'paypal' ? $rate : null,
                'status' => 'pending',
            ]);

            if ($couponCode) {
                // The coupon row is locked inside redeem(), which makes use-limit checks atomic.
                $this->coupons->redeem($couponCode, $subtotal, $shipping['email'], $request->user(), $order);
            }

            $order->load('items');
            $this->stock->reserve($order);

            return $order;
        });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('checkout.payment')->with('error', $e->errors()['coupon'][0] ?? 'Mã ưu đãi không còn hợp lệ.');
        }

        $confirmableOrders = (array) session('confirmation_order_ids', []);
        session(['confirmation_order_ids' => array_values(array_unique([...$confirmableOrders, $order->id]))]);

        if (in_array($data['payment_method'], ['cod', 'bank_transfer'], true)) {
            $cart->items()->delete();
            session()->forget('checkout');

            return redirect()->route('checkout.confirmation', $order);
        }

        return $data['payment_method'] === 'paypal'
            ? redirect()->route('payments.paypal.redirect', $order)
            : redirect()->route('payments.vnpay.redirect', $order);
    }

    public function confirmation(Request $request, Order $order)
    {
        $isOrderOwner = $request->user() !== null && $request->user()->id === $order->user_id;
        $isCurrentGuestSession = in_array($order->id, (array) session('confirmation_order_ids', []), true);
        abort_unless($isOrderOwner || $isCurrentGuestSession, 403);

        $order->load(['items', 'payments', 'shipments']);

        return view('store.confirmation', [
            'order' => $order,
            'bankTransfer' => $this->bankTransferDetails($order),
        ]);
    }

    /** @return array<string, bool> */
    private function paymentMethods(): array
    {
        $vnpayEnabled = filled(config('services.vnpay.tmn_code'))
            && filled(config('services.vnpay.hash_secret'));

        return [
            'bank_transfer' => filled(config('services.bank_transfer.account_number'))
                && filled(config('services.bank_transfer.bank_code')),
            'vnpay_debit' => $vnpayEnabled,
            'vnpay_credit' => $vnpayEnabled,
            'vnpay_qr' => $vnpayEnabled,
            'paypal' => filled(config('services.paypal.client_id'))
                && filled(config('services.paypal.secret')),
            'cod' => true,
        ];
    }

    /** @return array<string, string>|null */
    private function bankTransferDetails(Order $order): ?array
    {
        if (! $order->payments->contains('provider', 'bank_transfer')) {
            return null;
        }

        $bankCode = trim((string) config('services.bank_transfer.bank_code'));
        $accountNumber = trim((string) config('services.bank_transfer.account_number'));
        if ($bankCode === '' || $accountNumber === '') {
            return null;
        }

        // Keep the transfer memo short and without punctuation for banking-app compatibility.
        $transferReference = preg_replace('/[^A-Za-z0-9]/', '', $order->order_number) ?: (string) $order->id;
        $query = [
            'amount' => (string) $order->total_amount,
            'addInfo' => $transferReference,
        ];
        if (filled(config('services.bank_transfer.account_name'))) {
            $query['accountName'] = config('services.bank_transfer.account_name');
        }

        $imageUrl = rtrim((string) config('services.bank_transfer.vietqr_image_url'), '/')
            . '/' . rawurlencode($bankCode) . '-' . rawurlencode($accountNumber)
            . '-' . rawurlencode((string) config('services.bank_transfer.vietqr_template', 'compact2')) . '.png?'
            . http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return [
            'bank_name' => (string) config('services.bank_transfer.bank_name'),
            'account_number' => (string) config('services.bank_transfer.account_number_display'),
            'transfer_reference' => $transferReference,
            'qr_url' => $imageUrl,
        ];
    }

    private function couponPreview($cart, ?string $email = null): ?array
    {
        $code = $this->coupons->normalize(session('checkout.coupon_code'));
        if (! $code || $cart->items->isEmpty()) return null;

        try {
            return $this->coupons->preview(
                $code,
                (int) $cart->items->sum(fn ($item) => $item->unit_price_amount * $item->quantity),
                $email,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->forget('checkout.coupon_code');
            if ($email) throw $e;
            return null;
        }
    }
}
