@extends('layouts.store', ['title' => __('store.tracking.page_title').' | LUNAR JEWELS'])

@section('content')
@php
    $statusLabels = [
        'pending_confirmation' => __('store.status.pending_confirmation'), 'confirmed' => __('store.status.confirmed'), 'preparing' => __('store.status.preparing'),
        'shipping' => __('store.status.shipping'), 'completed' => __('store.status.completed'), 'cancelled' => __('store.status.cancelled'), 'returned' => __('store.status.returned'),
    ];
    $paymentLabels = ['unpaid' => __('store.payment_status.unpaid'), 'pending' => __('store.payment_status.pending'), 'paid' => __('store.payment_status.paid'), 'partially_refunded' => __('store.payment_status.partially_refunded'), 'refunded' => __('store.payment_status.refunded'), 'failed' => __('store.payment_status.failed')];
@endphp

<section class="tracking-page">
    <div class="shell tracking-grid">
        <div class="tracking-intro">
            <span class="eyebrow eyebrow-light">Guest order tracking</span>
            <h1 class="display">FOLLOW<br><em>the journey.</em></h1>
            <p>{{ __('store.tracking.copy') }}</p>
            <div class="tracking-orbit" aria-hidden="true"><span></span></div>
        </div>

        <div class="tracking-panel">
            <div class="tracking-panel-head"><span>01</span><div><span class="eyebrow">Find your order</span><h2>{{ __('store.tracking.title') }}</h2></div></div>
            <form class="tracking-form" method="POST" action="{{ route('tracking.search') }}">
                @csrf
                <label class="field"><span>{{ __('store.tracking.order_number') }}</span><input name="order_number" value="{{ old('order_number') }}" placeholder="{{ __('store.tracking.order_placeholder') }}" maxlength="50" autocapitalize="characters" required></label>
                <label class="field"><span>{{ __('store.tracking.phone') }}</span><input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" maxlength="30" required></label>
                <button class="button" type="submit">{{ __('store.tracking.submit') }} <span>→</span></button>
            </form>

            @isset($order)
                <div class="tracking-result">
                    <div class="tracking-result-head"><span class="eyebrow">{{ __('store.tracking.found') }}</span><h3>{{ $order->order_number }}</h3></div>
                    <div class="tracking-result-grid">
                        <div><span>{{ __('store.tracking.status') }}</span><b class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? str_replace('_', ' ', $order->status) }}</b></div>
                        <div><span>{{ __('store.tracking.payment') }}</span><b class="status-pill {{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</b></div>
                        <div><span>{{ __('store.tracking.total') }}</span><b><x-money :amount="$order->total_amount" /></b></div>
                    </div>
                    @foreach($order->shipments as $shipment)
                        <div class="tracking-shipment"><span>{{ __('store.tracking.shipping') }}</span><p><b>{{ $shipment->carrier ?: __('store.tracking.preparing') }}</b><small>{{ $shipment->tracking_number ?: __('store.tracking.no_tracking_number') }}</small></p></div>
                    @endforeach
                </div>
            @endisset
        </div>
    </div>
</section>
@endsection
