<?php

namespace App\Filament\Resources\ShippingZones;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\ShippingZones\Pages\ManageShippingZones;
use App\Models\ShippingZone;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingZoneResource extends AdminResource
{
    protected static ?string $model = ShippingZone::class;
    protected static string $viewPermission = 'shipping.view';
    protected static ?string $managePermission = 'shipping.manage';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    protected static ?string $navigationLabel = 'Khu vực giao hàng';
    protected static string|\UnitEnum|null $navigationGroup = 'Bán hàng';
    protected static ?int $navigationSort = 40;
    public static function form(Schema $schema): Schema { return $schema->components([
        TextInput::make('code')->label('Mã vùng')->required()->unique(ignoreRecord: true)->maxLength(50),
        TextInput::make('name')->label('Tên khu vực')->required()->maxLength(255),
        TextInput::make('fee_vnd')->label('Phí giao hàng (₫)')->numeric()->minValue(0)->required(),
        TextInput::make('free_shipping_threshold_vnd')->label('Ngưỡng miễn phí ship (₫)')->numeric()->minValue(0)->default(0)->required(),
        Toggle::make('is_active')->label('Đang hoạt động')->default(true)->required(),
    ])->columns(2); }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('code')->label('Mã')->searchable()->weight('bold'), TextColumn::make('name')->label('Khu vực')->searchable(),
        TextColumn::make('fee_vnd')->label('Phí')->money('VND', locale: 'vi'), TextColumn::make('free_shipping_threshold_vnd')->label('Miễn phí từ')->money('VND', locale: 'vi'),
        TextColumn::make('provinces_count')->counts('provinces')->label('Tỉnh'), TextColumn::make('wards_count')->counts('wards')->label('Phường/Xã'), IconColumn::make('is_active')->label('Bật')->boolean(),
    ])->recordActions([EditAction::make()->label('Sửa'), DeleteAction::make()->label('Xóa')])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]); }
    public static function getPages(): array { return ['index' => ManageShippingZones::route('/')]; }
}
