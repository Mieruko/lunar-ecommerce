@extends('layouts.store', ['title' => 'Yêu cầu bảo hành | LUNAR JEWELS'])

@section('content')
<section class="subpage-hero"><div class="shell subpage-hero-inner"><div><span class="eyebrow eyebrow-light">After sales</span><h1 class="display section-title">BẢO HÀNH</h1></div><p>Gửi thông tin phiếu bảo hành để đội ngũ kỹ thuật hỗ trợ bạn.</p></div></section>
<section class="page"><div class="shell"><form class="tracking-panel" method="POST" action="{{ route('support.warranty.submit') }}">@csrf
    <div class="tracking-panel-head"><span class="panel-index">01</span><div><span class="eyebrow">Warranty claim</span><h2>Gửi yêu cầu bảo hành</h2></div></div>
    <div class="form-grid"><label class="field"><span>Mã phiếu bảo hành</span><input name="warranty_number" value="{{ old('warranty_number') }}" required></label><label class="field"><span>Email đặt hàng</span><input type="email" name="email" value="{{ old('email') }}" required></label><label class="field"><span>Số điện thoại</span><input name="phone" value="{{ old('phone') }}" required></label><label class="field full"><span>Mô tả tình trạng</span><textarea name="description" rows="5" required>{{ old('description') }}</textarea></label></div>
    <button class="button" type="submit">Gửi yêu cầu <span>→</span></button>
</form></div></section>
@endsection
