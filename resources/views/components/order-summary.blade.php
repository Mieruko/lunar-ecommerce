{{--
    Unified order summary component (§21).
    Renders: Tạm tính → Giảm giá (voucher) → Vận chuyển → Tổng cộng.
    All amounts come straight from the backend $totals array — nothing is
    computed on the frontend.

    Props:
    - totals: ['subtotal', 'discount', 'shipping', 'total']
    - coupon: optional ['code' => ...] array when a voucher is applied
    - shippingPending: true when no shipping quote exists yet (shows "Chọn địa chỉ")
--}}
@props(['totals', 'coupon' => null, 'shippingPending' => false])

<div {{ $attributes->class(['summary-table']) }}>
    <div class="summary-row">
        <span>{{ __('store.order_summary.subtotal') }}</span>
        <b><x-money :amount="$totals['subtotal']" /></b>
    </div>

    @if(!empty($totals['discount']))
        <div class="summary-row summary-row-discount">
            <span>{{ __('store.order_summary.discount') }}{{ $coupon ? ' · '.$coupon['code'] : '' }}</span>
            <b class="summary-discount">− <x-money :amount="$totals['discount']" /></b>
        </div>
    @endif

    <div class="summary-row">
        <span>{{ __('store.order_summary.shipping') }}</span>
        <b data-shipping-fee>
            @if($shippingPending)
                {{ __('store.order_summary.choose_address') }}
            @elseif(!empty($totals['shipping']))
                <x-money :amount="$totals['shipping']" />
            @else
                {{ __('store.order_summary.free') }}
            @endif
        </b>
    </div>

    <div class="summary-row total">
        <span>{{ __('store.order_summary.total') }}</span>
        <span data-checkout-total><x-money :amount="$totals['total']" /></span>
    </div>
</div>
