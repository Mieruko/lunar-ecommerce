<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $todayRevenue = (int) Payment::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('amount');

        $monthRevenue = (int) Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $pendingOrders = Order::query()->where('status', 'pending_confirmation')->count();
        $processingOrders = Order::query()->whereIn('status', ['confirmed', 'preparing', 'shipping'])->count();
        $lowStock = Inventory::query()->whereRaw('(quantity_on_hand - quantity_reserved) <= reorder_level')->count();

        return [
            Stat::make('Doanh thu hôm nay', number_format($todayRevenue, 0, ',', '.').' ₫')
                ->description('Tháng này: '.number_format($monthRevenue, 0, ',', '.').' ₫')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Đơn chờ xác nhận', $pendingOrders)
                ->description('Cần xử lý sớm')
                ->icon('heroicon-o-bell-alert')
                ->color($pendingOrders > 0 ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Đơn đang xử lý', $processingOrders)
                ->description('Xác nhận → chuẩn bị → giao hàng')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Sản phẩm sắp hết', $lowStock)
                ->description('Tồn khả dụng ≤ mức cảnh báo')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'success')
                ->url(InventoryResource::getUrl('index')),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission('dashboard.view') ?? false;
    }
}
