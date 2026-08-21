<?php
namespace App\Filament\Resources\InventoryTransactions;
use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\InventoryTransactions\Pages\ManageInventoryTransactions;
use App\Models\InventoryTransaction;
use BackedEnum; use Filament\Schemas\Schema; use Filament\Support\Icons\Heroicon; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Filters\SelectFilter; use Filament\Tables\Table; use Illuminate\Database\Eloquent\Builder;
class InventoryTransactionResource extends AdminResource {
    protected static ?string $model = InventoryTransaction::class; protected static string $viewPermission = 'inventory.view'; protected static ?string $managePermission = null; protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock; protected static ?string $navigationLabel = 'Lịch sử kho'; protected static string|\UnitEnum|null $navigationGroup = 'Kho hàng'; protected static ?int $navigationSort = 20;
    private const TYPES = ['receive' => 'Nhập kho', 'sale' => 'Xuất bán', 'return' => 'Hoàn kho', 'adjustment' => 'Điều chỉnh', 'reservation' => 'Giữ chỗ', 'release' => 'Giải phóng'];
    public static function getEloquentQuery(): Builder { return InventoryTransaction::query(); }
    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function table(Table $table): Table { return $table->defaultSort('created_at', 'desc')->columns([TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i')->sortable(), TextColumn::make('product_variant_id')->label('ID biến thể')->numeric(), TextColumn::make('warehouse_id')->label('ID kho')->numeric(), TextColumn::make('transaction_type')->label('Loại')->badge()->formatStateUsing(fn ($s) => self::TYPES[$s] ?? $s), TextColumn::make('quantity_delta')->label('Thay đổi')->numeric()->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')), TextColumn::make('notes')->label('Ghi chú')->limit(50)])->filters([SelectFilter::make('transaction_type')->label('Loại')->options(self::TYPES)]); }
    public static function canCreate(): bool { return false; } public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; } public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function getPages(): array { return ['index' => ManageInventoryTransactions::route('/')]; }
}
