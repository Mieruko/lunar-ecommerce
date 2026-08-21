@extends('layouts.store', ['title' => 'Yêu cầu đổi trả | LUNAR JEWELS'])

@section('content')
<section class="subpage-hero"><div class="shell subpage-hero-inner"><div><span class="eyebrow eyebrow-light">After sales</span><h1 class="display section-title">ĐỔI TRẢ</h1></div><p>Gửi thông tin đơn hàng để đội ngũ chăm sóc khách hàng hỗ trợ bạn.</p></div></section>
<section class="page"><div class="shell"><form class="tracking-panel" method="POST" action="{{ route('support.return.submit') }}">@csrf
    <div class="tracking-panel-head"><span class="panel-index">01</span><div><span class="eyebrow">Return request</span><h2>Gửi yêu cầu đổi trả</h2></div></div>
    <div class="form-grid"><label class="field"><span>Mã đơn hàng</span><input name="order_number" value="{{ old('order_number') }}" required></label><label class="field"><span>Email đặt hàng</span><input type="email" name="email" value="{{ old('email') }}" required></label><label class="field"><span>Số điện thoại</span><input name="phone" value="{{ old('phone') }}" required></label><label class="field full"><span>Lý do yêu cầu</span><textarea name="reason" rows="5" required>{{ old('reason') }}</textarea></label></div>
    <button class="button" type="submit">Gửi yêu cầu <span>→</span></button>
</form></div></section>
@endsection
