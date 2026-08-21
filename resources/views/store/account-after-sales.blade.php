@extends('layouts.store', ['title' => 'Hậu mãi | LUNAR JEWELS'])

@section('content')
@php
    $returnLabels = ['requested' => 'Yêu cầu mới', 'approved' => 'Đã chấp nhận', 'rejected' => 'Từ chối', 'received' => 'Đã nhận hàng', 'refunded' => 'Đã hoàn tiền', 'closed' => 'Đã đóng'];
    $claimLabels = ['submitted' => 'Yêu cầu mới', 'approved' => 'Đã tiếp nhận', 'in_repair' => 'Đang xử lý', 'resolved' => 'Hoàn tất', 'rejected' => 'Từ chối'];
    $returnableOrders = auth()->user()->orders()->where('status', 'completed')->latest()->get();
    $activeWarranties = $warranties->filter(fn($warranty) => $warranty->status === 'active' && ! $warranty->ends_at->isPast());
@endphp
<section class="account-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">Care beyond purchase</span><h1 class="display section-title">HẬU MÃI</h1></div>
        <p>Quản lý bảo hành và đổi trả mà không cần nhập lại mã đơn.</p>
    </div>
</section>
<section class="page">
    <div class="shell account-layout lunar-account-layout">
        @include('store.partials.account-nav')
        <div class="account-content after-sales-content">
            <div class="after-sales-actions">
                <details class="after-sales-form-card">
                    <summary><span>↺</span><div><b>Gửi yêu cầu đổi trả</b><small>Dành cho đơn đã giao thành công</small></div><i>+</i></summary>
                    <form method="POST" action="{{ route('account.after-sales.returns.store') }}">
                        @csrf
                        <label class="field"><span>Chọn đơn hàng</span><select name="order_id" required><option value="">Chọn đơn đủ điều kiện</option>@foreach($returnableOrders as $order)<option value="{{ $order->id }}">{{ $order->order_number }} · {{ $order->created_at->format('d/m/Y') }}</option>@endforeach</select></label>
                        <label class="field"><span>Lý do đổi trả</span><textarea name="reason" rows="4" required maxlength="500"></textarea></label>
                        <button class="button" type="submit" @disabled($returnableOrders->isEmpty())>Gửi yêu cầu</button>
                    </form>
                </details>
                <details class="after-sales-form-card">
                    <summary><span>◇</span><div><b>Gửi yêu cầu bảo hành</b><small>Chọn trực tiếp phiếu đang hiệu lực</small></div><i>+</i></summary>
                    <form method="POST" action="{{ route('account.after-sales.warranties.store') }}">
                        @csrf
                        <label class="field"><span>Phiếu bảo hành</span><select name="warranty_id" required><option value="">Chọn phiếu bảo hành</option>@foreach($activeWarranties as $warranty)<option value="{{ $warranty->id }}">{{ $warranty->warranty_number }} · đến {{ $warranty->ends_at->format('d/m/Y') }}</option>@endforeach</select></label>
                        <label class="field"><span>Mô tả tình trạng</span><textarea name="description" rows="4" required maxlength="4000"></textarea></label>
                        <button class="button" type="submit" @disabled($activeWarranties->isEmpty())>Gửi yêu cầu</button>
                    </form>
                </details>
            </div>

            <section class="account-section">
                <div class="account-section-head"><span>06</span><div><span class="eyebrow">Warranty archive</span><h2>Phiếu bảo hành</h2></div></div>
                <div class="after-sales-list">
                    @forelse($warranties as $warranty)
                        <article><div><span>{{ $warranty->warranty_number }}</span><b>{{ $warranty->orderItem?->product_name }}</b><small>Đơn {{ $warranty->orderItem?->order?->order_number }} · {{ $warranty->starts_at->format('d/m/Y') }} — {{ $warranty->ends_at->format('d/m/Y') }}</small></div><span class="status-pill {{ $warranty->status }}">{{ $warranty->status === 'active' ? 'Còn hiệu lực' : ($warranty->status === 'expired' ? 'Hết hạn' : 'Vô hiệu') }}</span></article>
                    @empty
                        <div class="dashboard-empty"><p>Bạn chưa có phiếu bảo hành nào.</p></div>
                    @endforelse
                </div>
            </section>

            <section class="account-section">
                <div class="account-section-head"><span>07</span><div><span class="eyebrow">Service requests</span><h2>Tiến độ yêu cầu</h2></div></div>
                <div class="after-sales-list">
                    @foreach($returns as $return)
                        <article><div><span>Đổi trả · {{ $return->return_number }}</span><b>{{ $return->reason }}</b><small>Đơn {{ $return->order?->order_number }} · gửi {{ $return->requested_at->format('d/m/Y H:i') }}</small></div><span class="status-pill status-{{ $return->status }}">{{ $returnLabels[$return->status] ?? $return->status }}</span></article>
                    @endforeach
                    @foreach($claims as $claim)
                        <article><div><span>Bảo hành · {{ $claim->claim_number }}</span><b>{{ $claim->description }}</b><small>{{ $claim->resolution ?: 'Lunar Jewels đang tiếp nhận thông tin.' }}</small></div><span class="status-pill status-{{ $claim->status }}">{{ $claimLabels[$claim->status] ?? $claim->status }}</span></article>
                    @endforeach
                    @if($returns->isEmpty() && $claims->isEmpty())
                        <div class="dashboard-empty"><p>Bạn chưa gửi yêu cầu hậu mãi nào.</p></div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
