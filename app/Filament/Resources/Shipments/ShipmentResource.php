<?php

namespace App\Filament\Resources\Shipments;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\Shipments\Pages\ManageShipments;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\ShipmentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShipmentResource extends AdminResource
{
    protected static ?string $model = Shipment::class;

    protected static string $viewPermission = 'shipping.view';

    protected static ?string $managePermission = 'shipping.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Vận đơn';

    protected static ?string $modelLabel = 'vận đơn';

    protected static ?string $pluralModelLabel = 'vận đơn';

    protected static string|\UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 20;

    private const STATUSES = ['pending' => 'Chờ đóng gói', 'packed' => 'Đã đóng gói', 'shipped' => 'Đã gửi', 'delivered' => 'Đã giao', 'failed' => 'Giao thất bại', 'returned' => 'Hoàn hàng', 'cancelled' => 'Đã hủy'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('order_id')
                ->label('Đơn hàng đã chuẩn bị')
                ->relationship(
                    name: 'order',
                    titleAttribute: 'order_number',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->readyForShipment(),
                )
                ->getOptionLabelFromRecordUsing(fn (Order $record): string => $record->order_number.' — '.$record->customer_name)
                ->searchable(['order_number', 'customer_name', 'customer_phone'])
                ->preload()
                ->required()
                ->disabledOn('edit'),
            TextInput::make('carrier')->label('Đơn vị vận chuyển')->maxLength(255),
            TextInput::make('tracking_number')->label('Mã vận đơn')->maxLength(255),
            Select::make('status')->label('Trạng thái')->options(self::STATUSES)->required()->default('packed')->disabledOn('edit'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->with('order')->latest())
            ->columns([
                TextColumn::make('order.order_number')->label('Mã đơn')->searchable()->weight('bold'),
                TextColumn::make('order.customer_name')->label('Người nhận')->searchable(),
                TextColumn::make('order.customer_phone')->label('Điện thoại')->searchable()->copyable(),
                TextColumn::make('carrier')->label('Đơn vị')->placeholder('Chưa chọn'),
                TextColumn::make('tracking_number')->label('Mã vận đơn')->searchable()->copyable()->placeholder('Chưa có'),
                TextColumn::make('status')->label('Trạng thái')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state),
                TextColumn::make('order.payment_status')->label('Thanh toán')->badge(),
                TextColumn::make('shipped_at')->label('Ngày gửi')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])->filters([
                SelectFilter::make('status')->label('Trạng thái giao hàng')->options(self::STATUSES),
                SelectFilter::make('order_status')->label('Trạng thái đơn')->options([
                    'confirmed' => 'Đã xác nhận',
                    'preparing' => 'Đang chuẩn bị',
                    'shipping' => 'Đang giao',
                    'completed' => 'Hoàn tất',
                    'cancelled' => 'Đã hủy',
                ])->query(fn (Builder $query, array $data): Builder => $query->when(
                    $data['value'] ?? null,
                    fn (Builder $query, string $status): Builder => $query->whereHas('order', fn (Builder $order): Builder => $order->where('status', $status)),
                )),
                SelectFilter::make('payment_status')->label('Thanh toán')->options([
                    'unpaid' => 'Chưa thanh toán',
                    'pending' => 'Đang chờ',
                    'paid' => 'Đã thanh toán',
                    'refunded' => 'Đã hoàn tiền',
                ])->query(fn (Builder $query, array $data): Builder => $query->when(
                    $data['value'] ?? null,
                    fn (Builder $query, string $status): Builder => $query->whereHas('order', fn (Builder $order): Builder => $order->where('payment_status', $status)),
                )),
            ])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make()
                    ->label('Sửa thông tin')
                    ->visible(fn (Shipment $record): bool => in_array($record->status, ['pending', 'packed'], true)),
                self::statusAction('packed', 'Đã đóng gói', 'info'),
                self::statusAction('shipped', 'Bàn giao vận chuyển', 'primary'),
                self::statusAction('delivered', 'Đã giao', 'success'),
                self::statusAction('failed', 'Giao thất bại', 'danger'),
                self::statusAction('returned', 'Hoàn hàng', 'warning'),
            ])
            ->emptyStateHeading('Chưa có vận đơn')
            ->emptyStateDescription('Đơn đã chuẩn bị nhưng chưa có vận đơn sẽ xuất hiện ở hàng đợi phía trên.');
    }

    private static function statusAction(string $status, string $label, string $color): Action
    {
        return Action::make('set_'.$status)->label($label)->color($color)->requiresConfirmation()
            ->visible(fn (Shipment $record): bool => in_array($status, app(ShipmentService::class)->nextStatuses($record), true)
                && ! in_array($record->order?->status, ['cancelled', 'returned'], true))
            ->action(fn (Shipment $record) => app(ShipmentService::class)->updateStatus($record, $status));
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->readyForShipment()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return ['index' => ManageShipments::route('/')];
    }
}
