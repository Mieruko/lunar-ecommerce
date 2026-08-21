<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AfterSalesController extends Controller
{
    public function returnForm() { return view('store.return-request'); }

    public function submitReturn(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $order = Order::query()->where('order_number', $data['order_number'])
            ->whereRaw('lower(customer_email) = ?', [Str::lower($data['email'])])
            ->where('customer_phone', $data['phone'])->first();
        if (! $order) return back()->withInput()->withErrors(['order_number' => 'Thông tin đơn hàng không khớp.']);

        ReturnRequest::create(['order_id' => $order->id, 'return_number' => 'RT-'.now()->format('ymd').'-'.Str::upper(Str::random(6)), 'reason' => $data['reason'], 'status' => 'requested', 'requested_at' => now()]);
        return back()->with('success', 'Yêu cầu đổi trả đã được ghi nhận. Đội ngũ LUNAR JEWELS sẽ liên hệ với bạn.');
    }

    public function warrantyForm() { return view('store.warranty-claim'); }

    public function submitWarranty(Request $request)
    {
        $data = $request->validate([
            'warranty_number' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'description' => ['required', 'string', 'max:4000'],
        ]);
        $warranty = Warranty::query()->with('orderItem.order')->where('warranty_number', $data['warranty_number'])->first();
        $order = $warranty?->orderItem?->order;
        if (! $warranty || ! $order || Str::lower($order->customer_email) !== Str::lower($data['email']) || $order->customer_phone !== $data['phone']) {
            return back()->withInput()->withErrors(['warranty_number' => 'Thông tin bảo hành không khớp.']);
        }
        if ($warranty->status !== 'active' || $warranty->ends_at->isPast()) return back()->withInput()->withErrors(['warranty_number' => 'Phiếu bảo hành không còn hiệu lực.']);

        WarrantyClaim::create(['warranty_id' => $warranty->id, 'claim_number' => 'WC-'.now()->format('ymd').'-'.Str::upper(Str::random(6)), 'description' => $data['description'], 'status' => 'submitted']);
        return back()->with('success', 'Yêu cầu bảo hành đã được ghi nhận.');
    }
}
