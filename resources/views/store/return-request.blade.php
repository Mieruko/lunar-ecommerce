@extends('layouts.store', ['title' => __('store.after_sales.return_page_title').' | LUNAR JEWELS'])

@section('content')
<section class="subpage-hero"><div class="shell subpage-hero-inner"><div><span class="eyebrow eyebrow-light">After sales</span><h1 class="display section-title">{{ __('store.after_sales.return_heading') }}</h1></div><p>{{ __('store.after_sales.return_copy') }}</p></div></section>
<section class="page"><div class="shell"><form class="tracking-panel" method="POST" action="{{ route('support.return.submit') }}">@csrf
    <div class="tracking-panel-head"><span class="panel-index">01</span><div><span class="eyebrow">Return request</span><h2>{{ __('store.after_sales.return_title') }}</h2></div></div>
    <div class="form-grid"><label class="field"><span>{{ __('store.after_sales.order_number') }}</span><input name="order_number" value="{{ old('order_number') }}" required></label><label class="field"><span>{{ __('store.after_sales.order_email') }}</span><input type="email" name="email" value="{{ old('email') }}" required></label><label class="field"><span>{{ __('store.after_sales.phone') }}</span><input name="phone" value="{{ old('phone') }}" required></label><label class="field full"><span>{{ __('store.after_sales.reason') }}</span><textarea name="reason" rows="5" required>{{ old('reason') }}</textarea></label></div>
    <button class="button" type="submit">{{ __('store.after_sales.submit') }} <span>→</span></button>
</form></div></section>
@endsection
