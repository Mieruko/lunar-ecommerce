@extends('layouts.store', ['title' => __('store.after_sales.warranty_page_title').' | LUNAR JEWELS'])

@section('content')
<section class="subpage-hero"><div class="shell subpage-hero-inner"><div><span class="eyebrow eyebrow-light">After sales</span><h1 class="display section-title">{{ __('store.after_sales.warranty_heading') }}</h1></div><p>{{ __('store.after_sales.warranty_copy') }}</p></div></section>
<section class="page"><div class="shell"><form class="tracking-panel" method="POST" action="{{ route('support.warranty.submit') }}">@csrf
    <div class="tracking-panel-head"><span class="panel-index">01</span><div><span class="eyebrow">Warranty claim</span><h2>{{ __('store.after_sales.warranty_title') }}</h2></div></div>
    <div class="form-grid"><label class="field"><span>{{ __('store.after_sales.warranty_number') }}</span><input name="warranty_number" value="{{ old('warranty_number') }}" required></label><label class="field"><span>{{ __('store.after_sales.order_email') }}</span><input type="email" name="email" value="{{ old('email') }}" required></label><label class="field"><span>{{ __('store.after_sales.phone') }}</span><input name="phone" value="{{ old('phone') }}" required></label><label class="field full"><span>{{ __('store.after_sales.description') }}</span><textarea name="description" rows="5" required>{{ old('description') }}</textarea></label></div>
    <button class="button" type="submit">{{ __('store.after_sales.submit') }} <span>→</span></button>
</form></div></section>
@endsection
