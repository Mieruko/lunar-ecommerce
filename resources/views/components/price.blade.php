{{--
    Unified product price block (§20).
    Shows the current price, and — only when the backend provides a real
    compare-at price — the muted strikethrough original and a discount badge.
    No prices are ever invented on the frontend.
--}}
@props(['amount', 'compareAt' => null])
@php
    $hasCompare = $compareAt !== null && (float) $compareAt > (float) $amount;
    $percentOff = $hasCompare ? (int) round((1 - ((float) $amount / (float) $compareAt)) * 100) : 0;
@endphp
<span {{ $attributes->class(['price-block']) }}>
    <span class="price-current"><x-money :amount="$amount" /></span>
    @if($hasCompare)
        <s class="price-old"><x-money :amount="$compareAt" /></s>
        @if($percentOff > 0)<span class="price-off">-{{ $percentOff }}%</span>@endif
    @endif
</span>
