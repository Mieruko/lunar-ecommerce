<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrders extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Đơn hàng mới cần theo dõi')
            ->description('Bấm vào một đơn để xem sản phẩm, địa chỉ, thanh toán và lịch sử xử lý.')
            ->query(
                Order::query()
                    ->whereIn('status', ['pending_confirmation', 'confirmed', 'preparing', 'shipping'])
                    ->latest('placed_at')
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('Mã đơn')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Khách hàng')
                    ->description(fn (Order $record): string => $record->customer_phone),
                TextColumn::make('total_amount')
                    ->label('Tổng tiền')
                    ->money('VND', locale: 'vi')
                    ->weight('bold'),
                TextColumn::make('payment_status')
                    ->label('Thanh toán')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderResource::paymentStatusOptions()[$state] ?? $state),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderResource::statusOptions()[$state] ?? $state),
                TextColumn::make('placed_at')
                    ->label('Ngày đặt')
                    ->dateTime('d/m H:i'),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission('orders.view') ?? false;
    }
}
