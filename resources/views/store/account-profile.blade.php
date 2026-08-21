@extends('layouts.store', ['title' => 'Hồ sơ | LUNAR JEWELS'])

@section('content')
<section class="account-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">Personal details</span><h1 class="display section-title">HỒ SƠ</h1></div>
        <div class="account-identity"><span>Đang đăng nhập</span><b>{{ $user->name }}</b><small>{{ $user->email }}</small></div>
    </div>
</section>

<section class="page">
    <div class="shell account-layout lunar-account-layout">
        @include('store.partials.account-nav')
        <div class="account-content">
            <section class="account-section">
                <div class="account-section-head"><span>07</span><div><span class="eyebrow">Personal details</span><h2>Hồ sơ cá nhân</h2></div></div>
                <form method="POST" action="{{ route('account.profile.update') }}">
                    @csrf @method('PATCH')
                    <div class="form-grid lunar-form-grid">
                        <label class="field"><span>Họ và tên</span><input name="name" value="{{ old('name', $user->name) }}" required></label>
                        <label class="field"><span>Số điện thoại</span><input name="phone" value="{{ old('phone', $user->phone) }}"></label>
                        <label class="field full"><span>Email</span><input value="{{ $user->email }}" disabled></label>
                    </div>
                    <div class="account-save-row"><button class="button" type="submit">Lưu thay đổi</button></div>
                </form>
            </section>

            <section class="account-section">
                <div class="account-section-head"><span>08</span><div><span class="eyebrow">Delivery book</span><h2>Địa chỉ giao hàng</h2></div></div>
                <form method="POST" action="{{ route('account.addresses.store') }}" data-vn-address-form data-provinces-url="{{ route('checkout.locations.provinces') }}" data-wards-url="{{ route('checkout.locations.wards') }}">
                    @csrf
                    <div class="form-grid lunar-form-grid">
                        <label class="field"><span>Người nhận</span><input name="recipient_name" value="{{ old('recipient_name') }}" required></label>
                        <label class="field"><span>Số điện thoại</span><input name="phone" value="{{ old('phone', $user->phone) }}" required></label>
                        <label class="field full"><span>Địa chỉ</span><input name="line_1" value="{{ old('line_1') }}" required></label>
                        <label class="field"><span>Tỉnh / Thành phố</span><select name="province_code" data-vn-province data-selected="{{ old('province_code') }}" required><option value="">Đang tải Tỉnh / Thành...</option></select></label>
                        <label class="field"><span>Phường / Xã / Đặc khu</span><select name="ward_code" data-vn-ward data-selected="{{ old('ward_code') }}" disabled required><option value="">Chọn Tỉnh / Thành trước</option></select></label>
                        <label class="field full remember-row"><input type="checkbox" name="is_default_shipping" value="1"><span>Đặt làm địa chỉ giao hàng mặc định</span></label>
                    </div>
                    <div class="account-save-row"><button class="button-outline" type="submit">Thêm địa chỉ</button></div>
                </form>

                <div class="saved-addresses">
                    @forelse($addresses as $address)
                        <article class="address-card">
                            <span class="address-index">{{ str_pad((string)$loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <div>
                                <b>{{ $address->recipient_name }} @if($address->is_default_shipping)<small>· Mặc định</small>@endif</b>
                                <small>{{ $address->phone }}</small><p>{{ $address->line_1 }}, {{ $address->ward }}, {{ $address->province }}</p>
                                <div class="cart-item-actions">
                                    @unless($address->is_default_shipping)<form method="POST" action="{{ route('account.addresses.default', $address) }}">@csrf @method('PATCH')<button class="cart-text-action" type="submit">Đặt mặc định</button></form>@endunless
                                    <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">@csrf @method('DELETE')<button class="cart-text-action danger-action" type="submit">Xóa</button></form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="account-empty-row">Chưa lưu địa chỉ nào.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
