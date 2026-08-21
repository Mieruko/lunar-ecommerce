<?php

namespace App\Filament\Resources\Shipments;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\Shipments\Pages\ManageShipments;
use App\Models\Shipment;
use App\Services\ShipmentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
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
    protected static string|\UnitEnum|null $navigationGroup = 'Bán hàng';
    protected static ?int $navigationSort = 20;
    private const STATUSES = ['pending' => 'Chờ đóng gói', 'packed' => 'Đã đóng gói', 'shipped' => 'Đã gửi', 'delivered' => 'Đã giao', 'failed' => 'Giao thất bại', 'returned' => 'Hoàn hàng'];

    public static function form(Schema $schema): Schema { return $schema->components([
        Select::make('order_id')->relationship('order', 'order_number')->searchable()->preload()->required()->disabledOn('edit'),
        TextInput::make('carrier')->label('Đơn vị vận chuyển')->maxLength(255),
        TextInput::make('tracking_number')->label('Mã vận đơn')->maxLength(255),
        Select::make('status')->label('Trạng thái')->options(self::STATUSES)->required()->default('pending')->disabledOn('edit'),
    ])->columns(2); }
    public static function table(Table $table): Table { return $table->modifyQueryUsing(fn (Builder $query) => $query->with('order')->latest())
        ->columns([
            TextColumn::make('order.order_number')->label('Mã đơn')->searchable()->weight('bold'),
            TextColumn::make('carrier')->label('Đơn vị')->placeholder('Chưa chọn'),
            TextColumn::make('tracking_number')->label('Mã vận đơn')->searchable()->copyable()->placeholder('Chưa có'),
            TextColumn::make('status')->label('Trạng thái')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state]),
            TextColumn::make('shipped_at')->label('Ngày gửi')->dateTime('d/m/Y H:i')->placeholder('—'),
        ])->filters([SelectFilter::make('status')->label('Trạng thái')->options(self::STATUSES)])
        ->recordActions([EditAction::make()->label('Sửa'), self::statusAction('packed', 'Đóng gói'), self::statusAction('shipped', 'Đã gửi'), self::statusAction('delivered', 'Đã giao'), self::statusAction('returned', 'Hoàn hàng')]); }
    private static function statusAction(string $status, string $label): Action { return Action::make('set_'.$status)->label($label)->requiresConfirmation()->visible(fn (Shipment $record) => $record->status !== $status)->action(fn (Shipment $record) => app(ShipmentService::class)->updateStatus($record, $status)); }
    public static function getPages(): array { return ['index' => ManageShipments::route('/')]; }
}
