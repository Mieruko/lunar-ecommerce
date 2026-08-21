<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\WishlistItem;
use App\Services\VietnamAddressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private VietnamAddressService $addresses) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $paidOrderTotal = (int) $user->orders()
            ->whereIn('payment_status', ['paid', 'partially_refunded'])
            ->sum('total_amount');
        $refundedTotal = (int) Refund::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->sum('amount');

        $recentOrders = $user->orders()
            ->with(['items', 'shipments'])
            ->latest()
            ->limit(3)
            ->get();

        $activeOrder = $user->orders()
            ->with(['items', 'shipments', 'statusHistory'])
            ->whereIn('status', ['pending_confirmation', 'confirmed', 'preparing', 'shipping'])
            ->latest()
            ->first();

        $email = Str::lower(trim((string) $user->email));
        $coupons = Coupon::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit'))
            ->withCount(['redemptions as customer_usage_count' => fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereRaw('lower(customer_email) = ?', [$email])])
            ->orderBy('ends_at')
            ->limit(6)
            ->get()
            ->filter(fn (Coupon $coupon) => $coupon->per_customer_limit === null
                || $coupon->customer_usage_count < $coupon->per_customer_limit)
            ->take(3);

        $stats = [
            'orders' => $user->orders()->count(),
            'spent' => max(0, $paidOrderTotal - $refundedTotal),
            'notifications' => $user->unreadNotifications()->count(),
            'warranties' => Warranty::query()
                ->where('status', 'active')
                ->whereHas('orderItem.order', fn ($query) => $query->where('user_id', $user->id))
                ->count(),
            'wishlist' => WishlistItem::query()
                ->whereHas('wishlist', fn ($query) => $query->where('user_id', $user->id))
                ->count(),
        ];

        return view('store.account-dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'activeOrder' => $activeOrder,
            'coupons' => $coupons,
            'notifications' => $user->notifications()->limit(5)->get(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('store.account-profile', [
            'user' => $request->user(),
            'addresses' => $request->user()->addresses()
                ->orderByDesc('is_default_shipping')
                ->latest()
                ->get(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone,'.$request->user()->id],
        ]);
        $request->user()->update($data);

        return back()->with('success', 'Đã cập nhật hồ sơ.');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'line_1' => ['required', 'string', 'max:255'],
            'province_code' => ['required', 'string', 'size:2'],
            'ward_code' => ['required', 'string', 'size:5'],
            'is_default_shipping' => ['nullable', 'boolean'],
        ]);
        $resolved = $this->addresses->resolve($data['province_code'], $data['ward_code']);

        DB::transaction(function () use ($request, $data, $resolved) {
            $user = $request->user();
            $default = $request->boolean('is_default_shipping') || ! $user->addresses()->exists();
            if ($default) {
                $user->addresses()->update(['is_default_shipping' => false]);
            }
            $user->addresses()->create([
                ...$data,
                'province' => $resolved['province']->full_name,
                'ward' => $resolved['ward']->full_name,
                'district' => null,
                'country_code' => 'VN',
                'is_default_shipping' => $default,
            ]);
        });

        return back()->with('success', 'Đã lưu địa chỉ giao hàng.');
    }

    public function destroyAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $wasDefault = $address->is_default_shipping;
        $address->delete();
        if ($wasDefault) {
            $request->user()->addresses()->oldest()->first()?->update(['is_default_shipping' => true]);
        }

        return back()->with('success', 'Đã xóa địa chỉ.');
    }

    public function makeDefaultAddress(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_default_shipping' => false]);
            $address->update(['is_default_shipping' => true]);
        });

        return back()->with('success', 'Đã đặt địa chỉ mặc định.');
    }

    public function orders(Request $request): View
    {
        $query = Order::query()
            ->with(['items', 'payments', 'shipments'])
            ->where('user_id', $request->user()->id);

        match ($request->string('status')->toString()) {
            'active' => $query->whereIn('status', ['pending_confirmation', 'confirmed', 'preparing', 'shipping']),
            'completed' => $query->where('status', 'completed'),
            'closed' => $query->whereIn('status', ['cancelled', 'returned']),
            default => null,
        };

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('store.account-orders', compact('orders'));
    }

    public function order(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load([
            'items.warranties',
            'payments',
            'shipments',
            'statusHistory' => fn ($query) => $query->oldest(),
        ]);

        return view('store.account-order', compact('order'));
    }

    public function notifications(Request $request): View
    {
        return view('store.account-notifications', [
            'notifications' => $request->user()->notifications()->paginate(15),
        ]);
    }

    public function readNotification(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $actionUrl = data_get($item->data, 'action_url');

        return $actionUrl ? redirect()->to($actionUrl) : back();
    }

    public function readAllNotifications(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    public function benefits(Request $request): View
    {
        $user = $request->user();
        $email = Str::lower(trim((string) $user->email));
        $coupons = Coupon::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->withCount(['redemptions as customer_usage_count' => fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereRaw('lower(customer_email) = ?', [$email])])
            ->orderBy('ends_at')
            ->paginate(12);

        return view('store.account-benefits', compact('coupons'));
    }

    public function wishlist(Request $request): View
    {
        $items = WishlistItem::query()
            ->with(['product.brand', 'product.primaryImage', 'product.images', 'product.variants.inventory'])
            ->whereHas('wishlist', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(12);

        return view('store.account-wishlist', compact('items'));
    }

    public function toggleWishlist(Request $request, Product $product): RedirectResponse
    {
        $wishlist = $request->user()->wishlists()->firstOrCreate([], ['name' => 'Yêu thích']);
        $existing = $wishlist->items()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Đã bỏ sản phẩm khỏi danh sách yêu thích.');
        }

        $wishlist->items()->create(['product_id' => $product->id]);

        return back()->with('success', 'Đã lưu sản phẩm vào danh sách yêu thích.');
    }

    public function afterSales(Request $request): View
    {
        $userId = $request->user()->id;
        $returns = ReturnRequest::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('user_id', $userId))
            ->latest()
            ->get();
        $warranties = Warranty::query()
            ->with(['orderItem.order', 'claims'])
            ->whereHas('orderItem.order', fn ($query) => $query->where('user_id', $userId))
            ->latest()
            ->get();
        $claims = WarrantyClaim::query()
            ->with('warranty.orderItem.order')
            ->whereHas('warranty.orderItem.order', fn ($query) => $query->where('user_id', $userId))
            ->latest()
            ->get();

        return view('store.account-after-sales', compact('returns', 'warranties', 'claims'));
    }

    public function submitReturn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $order = $request->user()->orders()
            ->whereKey($data['order_id'])
            ->where('status', 'completed')
            ->firstOrFail();

        if ($order->returnRequests()->whereNotIn('status', ['rejected', 'closed'])->exists()) {
            return back()->withErrors(['order_id' => 'Đơn hàng đã có một yêu cầu đổi trả đang được xử lý.']);
        }

        ReturnRequest::create([
            'order_id' => $order->id,
            'return_number' => 'RT-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'reason' => $data['reason'],
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        return back()->with('success', 'Yêu cầu đổi trả đã được ghi nhận.');
    }

    public function submitWarranty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warranty_id' => ['required', 'integer'],
            'description' => ['required', 'string', 'max:4000'],
        ]);
        $warranty = Warranty::query()
            ->whereKey($data['warranty_id'])
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', today())
            ->whereHas('orderItem.order', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();

        if ($warranty->claims()->whereNotIn('status', ['resolved', 'rejected'])->exists()) {
            return back()->withErrors(['warranty_id' => 'Phiếu bảo hành đang có một yêu cầu được xử lý.']);
        }

        WarrantyClaim::create([
            'warranty_id' => $warranty->id,
            'claim_number' => 'WC-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'description' => $data['description'],
            'status' => 'submitted',
        ]);

        return back()->with('success', 'Yêu cầu bảo hành đã được ghi nhận.');
    }
}
