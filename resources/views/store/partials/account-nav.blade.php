<aside class="account-sidebar">
    <span class="account-sidebar-label">My Lunar</span>
    <nav class="account-nav account-nav-expanded" aria-label="Khu vực khách hàng">
        <a @class(['active' => request()->routeIs('account.dashboard')]) href="{{ route('account.dashboard') }}"><span>01</span> Tổng quan</a>
        <a @class(['active' => request()->routeIs('account.orders*')]) href="{{ route('account.orders') }}"><span>02</span> Đơn hàng</a>
        <a @class(['active' => request()->routeIs('account.notifications*')]) href="{{ route('account.notifications') }}"><span>03</span> Thông báo @if(auth()->user()->unreadNotifications()->count())<b class="account-nav-badge">{{ auth()->user()->unreadNotifications()->count() }}</b>@endif</a>
        <a @class(['active' => request()->routeIs('account.benefits')]) href="{{ route('account.benefits') }}"><span>04</span> Ưu đãi</a>
        <a @class(['active' => request()->routeIs('account.wishlist*')]) href="{{ route('account.wishlist') }}"><span>05</span> Yêu thích</a>
        <a @class(['active' => request()->routeIs('account.after-sales')]) href="{{ route('account.after-sales') }}"><span>06</span> Hậu mãi</a>
        <a @class(['active' => request()->routeIs('account.profile*')]) href="{{ route('account.profile') }}"><span>07</span> Hồ sơ & địa chỉ</a>
    </nav>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="account-logout" type="submit">Đăng xuất <span>↗</span></button>
    </form>
</aside>
