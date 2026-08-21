<x-filament-panels::page>
    <form class="flex flex-wrap items-end gap-4" method="GET">
        <label class="grid gap-1 text-sm font-medium">Từ ngày <input class="fi-input rounded-lg" type="date" name="from" value="{{ $from->format('Y-m-d') }}"></label>
        <label class="grid gap-1 text-sm font-medium">Đến ngày <input class="fi-input rounded-lg" type="date" name="to" value="{{ $to->format('Y-m-d') }}"></label>
        <button class="fi-btn fi-btn-color-primary" type="submit">Xem báo cáo</button>
        @if(auth()->user()?->hasPermission('reports.export'))<a class="fi-btn fi-btn-color-gray" href="{{ route('admin.reports.export',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}">Xuất CSV</a>@endif
    </form>
    <div class="grid gap-4 md:grid-cols-4"><x-filament::section><p class="text-sm text-gray-500">Doanh thu đã thu</p><p class="text-2xl font-bold">{{ number_format($revenue,0,',','.') }} ₫</p></x-filament::section><x-filament::section><p class="text-sm text-gray-500">Đơn hàng</p><p class="text-2xl font-bold">{{ $orders }}</p></x-filament::section><x-filament::section><p class="text-sm text-gray-500">Mã ưu đãi đã dùng</p><p class="text-2xl font-bold">{{ $couponRedemptions }}</p></x-filament::section><x-filament::section><p class="text-sm text-gray-500">Sắp hết hàng</p><p class="text-2xl font-bold">{{ $lowStock }}</p></x-filament::section></div>
    <x-filament::section heading="Sản phẩm bán chạy"><table class="w-full text-sm"><thead><tr class="text-left"><th>Sản phẩm</th><th>Đã bán</th><th>Doanh thu</th></tr></thead><tbody>@forelse($bestSellers as $item)<tr><td class="py-2">{{ $item->product_name }}</td><td>{{ $item->units }}</td><td>{{ number_format($item->revenue,0,',','.') }} ₫</td></tr>@empty<tr><td colspan="3" class="py-4 text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></x-filament::section>
</x-filament-panels::page>
