@props(['amount'])
<span {{ $attributes->class(['money']) }}><span class="money-value">{{ number_format((float) $amount, 0, ',', '.') }}</span><span class="money-currency">₫</span></span>
