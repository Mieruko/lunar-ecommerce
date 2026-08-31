@extends('layouts.store', ['title' => __('store.confirmation.page_title').' | LUNAR JEWELS'])

@section('content')
@php
    $statusLabels = collect(['pending_confirmation', 'confirmed', 'preparing', 'shipping', 'completed', 'cancelled', 'returned'])
        ->mapWithKeys(fn ($status) => [$status => __("store.status.{$status}")])->all();
    $paymentLabels = collect(['unpaid', 'pending', 'paid', 'partially_refunded', 'refunded', 'failed'])
        ->mapWithKeys(fn ($status) => [$status => __("store.payment_status.{$status}")])->all();
@endphp

<section class="confirmation-page">
    <div class="confirmation-orbit" aria-hidden="true"><span></span><i>✦</i></div>
    <div class="shell confirmation-shell" data-reveal-group>
        <span class="eyebrow eyebrow-light" data-reveal="fade">{{ __('store.confirmation.received') }}</span>
        <h1 class="display" data-reveal>{!! __('store.confirmation.thank_you') !!}</h1>
        <p class="confirmation-lead" data-reveal>{{ __('store.confirmation.lead') }}</p>

        <div class="confirmation-card" data-reveal="scale">
            <div class="confirmation-order-number"><span>{{ __('store.confirmation.order_number') }}</span><b>{{ $order->order_number }}</b></div>
            <div class="confirmation-statuses">
                <span class="status-pill {{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span>
                <span class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? str_replace('_', ' ', $order->status) }}</span>
            </div>
            <div class="confirmation-total"><span>{{ __('store.confirmation.total') }}</span><b><x-money :amount="$order->total_amount" /></b></div>
        </div>

        @if($bankTransfer)
            <section class="transfer-instructions" aria-labelledby="transfer-title" data-reveal>
                <div class="transfer-copy">
                    <span class="eyebrow eyebrow-light">{{ __('store.confirmation.bank_transfer') }}</span>
                    <h2 id="transfer-title">{{ __('store.confirmation.scan_title') }}</h2>
                    <p>{{ __('store.confirmation.scan_copy') }}</p>
                    <dl>
                        <div><dt>{{ __('store.confirmation.bank') }}</dt><dd>{{ $bankTransfer['bank_name'] }}</dd></div>
                        <div><dt>{{ __('store.confirmation.account_number') }}</dt><dd>{{ $bankTransfer['account_number'] }}</dd></div>
                        <div><dt>{{ __('store.confirmation.reference') }}</dt><dd>{{ $bankTransfer['transfer_reference'] }}</dd></div>
                        <div><dt>{{ __('store.confirmation.amount') }}</dt><dd><x-money :amount="$order->total_amount" /></dd></div>
                    </dl>
                </div>
                <img class="transfer-qr" src="{{ $bankTransfer['qr_url'] }}" alt="{{ __('store.confirmation.qr_alt', ['order' => $order->order_number]) }}">
            </section>
        @endif

        <div class="confirmation-actions" data-reveal="fade">
            @auth
                <a class="button button-light" href="{{ route('account.orders.show', $order) }}">{{ __('store.confirmation.view_progress') }} <span>→</span></a>
            @else
                <a class="button button-light" href="{{ route('tracking.form') }}">{{ __('store.confirmation.track_order') }} <span>→</span></a>
            @endauth
            @guest<a class="text-link text-link-light" href="{{ route('register') }}">{{ __('store.confirmation.create_account') }}</a>@endguest
            @auth<a class="text-link text-link-light" href="{{ route('account.orders') }}">{{ __('store.confirmation.my_orders') }}</a>@endauth
        </div>
    </div>
</section>
@endsection
