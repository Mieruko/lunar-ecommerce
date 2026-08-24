<?php

namespace App\Filament\Resources\Shipments\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\ShipmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ReadyForShipmentOrders extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Đơn hàng chờ tạo vận đơn')
            ->description('Chỉ hiển thị đơn đang chuẩn bị và chưa có vận đơn. Tạo xong, đơn sẽ tự rời khỏi danh sách này.')
            ->query(
                Order::query()
                    ->readyForShipment()
                    ->with('shippingAddress')
                    ->withCount('items')
                    ->oldest('updated_at')
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('Mã đơn đã chuẩn bị')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Người nhận')
                    ->description(fn (Order $record): string => $record->customer_phone)
                    ->searchable(['customer_name', 'customer_phone']),
                TextColumn::make('shippingAddress.province')
                    ->label('Tỉnh/Thành')
                    ->placeholder('—'),
                TextColumn::make('items_count')
                    ->label('Sản phẩm')
                    ->suffix(' SP')
                    ->alignCenter(),
                TextColumn::make('payment_status')
                    ->label('Thanh toán')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderResource::paymentStatusOptions()[$state] ?? $state),
                TextColumn::make('updated_at')
                    ->label('Sẵn sàng lúc')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('createShipment')
                    ->label('Tạo vận đơn')
                    ->icon(Heroicon::OutlinedTruck)
                    ->color('primary')
                    ->modalHeading(fn (Order $record): string => 'Tạo vận đơn · '.$record->order_number)
                    ->modalDescription('Nhập thông tin thật từ đơn vị vận chuyển. Mỗi đơn hàng chỉ được tạo một vận đơn.')
                    ->schema([
                        TextInput::make('carrier')
                            ->label('Đơn vị vận chuyển')
                            ->datalist([
                                'GHN',
                                'GHTK',
                                'Viettel Post',
                                'J&T Express',
                                'Ninja Van',
                                'GrabExpress',
                                'Ahamove',
                                'DHL',
                                'FedEx',
                            ])
                            ->required()
                            ->maxLength(255),
                        TextInput::make('tracking_number')
                            ->label('Mã vận đơn thực tế')
                            ->helperText('Dùng đúng mã do hãng vận chuyển cung cấp để khách có thể tra cứu.')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->authorize(fn (): bool => auth()->user()?->hasPermission('shipping.manage') ?? false)
                    ->action(function (Order $record, array $data): void {
                        app(ShipmentService::class)->createForOrder(
                            $record,
                            $data['carrier'],
                            $data['tracking_number'],
                        );

                        // The queue, shipment table, and navigation badge are
                        // separate Livewire components, so refresh all three.
                        $this->resetTable();
                        $this->dispatch('refresh-page');
                        $this->dispatch('refresh-sidebar');
                    })
                    ->successNotificationTitle('Đã tạo vận đơn'),
            ])
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->emptyStateHeading('Không có đơn chờ tạo vận đơn')
            ->emptyStateDescription('Khi một đơn chuyển sang “Đang chuẩn bị”, mã đơn sẽ tự xuất hiện tại đây.')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission('shipping.view') ?? false;
    }
}
