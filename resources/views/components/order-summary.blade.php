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
        <span>Tạm tính</span>
        <b><x-money :amount="$totals['subtotal']" /></b>
    </div>

    @if(!empty($totals['discount']))
        <div class="summary-row summary-row-discount">
            <span>Giảm giá{{ $coupon ? ' · '.$coupon['code'] : '' }}</span>
            <b class="summary-discount">− <x-money :amount="$totals['discount']" /></b>
        </div>
    @endif

    <div class="summary-row">
        <span>Vận chuyển</span>
        <b data-shipping-fee>
            @if($shippingPending)
                Chọn địa chỉ
            @elseif(!empty($totals['shipping']))
                <x-money :amount="$totals['shipping']" />
            @else
                Miễn phí
            @endif
        </b>
    </div>

    <div class="summary-row total">
        <span>Tổng cộng</span>
        <span data-checkout-total><x-money :amount="$totals['total']" /></span>
    </div>
</div>
