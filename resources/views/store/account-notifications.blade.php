@extends('layouts.store', ['title' => __('store.notifications.page_title').' | LUNAR JEWELS'])

@section('content')
<section class="account-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">{{ __('store.notifications.eyebrow') }}</span><h1 class="display section-title">{{ __('store.notifications.title') }}</h1></div>
        <p>{{ __('store.notifications.hero_copy') }}</p>
    </div>
</section>
<section class="page">
    <div class="shell account-layout lunar-account-layout">
        @include('store.partials.account-nav')
        <div class="account-content">
            <section class="account-section notifications-page-panel">
                <div class="account-section-head notification-page-head">
                    <span>03</span>
                    <div><span class="eyebrow">{{ __('store.notifications.centre') }}</span><h2>{{ __('store.notifications.page_heading') }}</h2></div>
                    @if(auth()->user()->unreadNotifications()->exists())
                        <form method="POST" action="{{ route('account.notifications.read-all') }}">@csrf<button class="button-outline" type="submit">{{ __('store.notifications.mark_read') }}</button></form>
                    @endif
                </div>
                <div class="notifications-page-list">
                    @forelse($notifications as $notification)
                        @include('store.partials.notification-row', ['notification' => $notification])
                    @empty
                        <div class="account-simple-empty"><span>○</span><h3>{{ __('store.notifications.empty_title') }}</h3><p>{{ __('store.notifications.empty_copy') }}</p></div>
                    @endforelse
                </div>
                <div class="pagination lunar-pagination">{{ $notifications->links() }}</div>
            </section>
        </div>
    </div>
</section>
@endsection
