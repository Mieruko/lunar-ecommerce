<?php
namespace App\Filament\Resources\Warehouses;
use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\Warehouses\Pages\ManageWarehouses;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Actions\BulkActionGroup; use Filament\Actions\DeleteAction; use Filament\Actions\DeleteBulkAction; use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput; use Filament\Forms\Components\Toggle; use Filament\Schemas\Schema; use Filament\Support\Icons\Heroicon; use Filament\Tables\Columns\IconColumn; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Table;
class WarehouseResource extends AdminResource {
    protected static ?string $model = Warehouse::class; protected static string $viewPermission = 'inventory.view'; protected static ?string $managePermission = 'inventory.adjust'; protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2; protected static ?string $navigationLabel = 'Kho'; protected static string|\UnitEnum|null $navigationGroup = 'Kho hàng'; protected static ?int $navigationSort = 5;
    public static function form(Schema $schema): Schema { return $schema->components([TextInput::make('code')->label('Mã kho')->required()->unique(ignoreRecord: true), TextInput::make('name')->label('Tên kho')->required(), TextInput::make('province')->label('Tỉnh/Thành'), TextInput::make('country_code')->label('Quốc gia')->default('VN')->required()->maxLength(2), Toggle::make('is_active')->label('Đang hoạt động')->default(true)] )->columns(2); }
    public static function table(Table $table): Table { return $table->columns([TextColumn::make('code')->label('Mã')->searchable()->weight('bold'), TextColumn::make('name')->label('Tên kho')->searchable(), TextColumn::make('province')->label('Khu vực'), TextColumn::make('inventory_count')->counts('inventory')->label('Mặt hàng'), IconColumn::make('is_active')->label('Bật')->boolean()])->recordActions([EditAction::make()->label('Sửa'), DeleteAction::make()->label('Xóa')])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]); }
    public static function getPages(): array { return ['index' => ManageWarehouses::route('/')]; }
}
