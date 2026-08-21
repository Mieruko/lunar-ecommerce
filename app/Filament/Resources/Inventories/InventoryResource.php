<?php

namespace App\Filament\Resources\Inventories;

use App\Filament\Resources\Inventories\Pages\ManageInventories;
use App\Models\Inventory;
use App\Services\InventoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
    protected static ?string $navigationLabel = 'Tồn kho';
    protected static ?string $modelLabel = 'tồn kho';
    protected static ?string $pluralModelLabel = 'tồn kho';
    protected static string|\UnitEnum|null $navigationGroup = 'Kho hàng';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['warehouse', 'variant.product']))
            ->columns([
                TextColumn::make('variant.product.name')
                    ->label('Sản phẩm')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('warehouse.name')
                    ->label('Kho')
                    ->sortable(),
                TextColumn::make('quantity_on_hand')
                    ->label('Tồn thực tế')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('quantity_reserved')
                    ->label('Đang giữ')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('available_quantity')
                    ->label('Khả dụng')
                    ->state(fn (Inventory $record): int => max(0, $record->quantity_on_hand - $record->quantity_reserved))
                    ->numeric()
                    ->badge()
                    ->color(fn (int $state, Inventory $record): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= $record->reorder_level => 'warning',
                        default => 'success',
                    })
                    ->alignCenter(),
                TextColumn::make('reorder_level')
                    ->label('Mức cảnh báo')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('stock_status')
                    ->label('Tình trạng')
                    ->state(function (Inventory $record): string {
                        $available = max(0, $record->quantity_on_hand - $record->quantity_reserved);

                        return match (true) {
                            $available <= 0 => 'Hết hàng',
                            $available <= $record->reorder_level => 'Sắp hết',
                            default => 'Còn hàng',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Hết hàng' => 'danger',
                        'Sắp hết' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Chỉ xem sắp hết / hết hàng')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('(quantity_on_hand - quantity_reserved) <= reorder_level')),
            ])
            ->recordActions([
                self::quantityAction('receive', 'Nhập kho', 'receive', 'success', true),
                self::quantityAction('adjust', 'Điều chỉnh', 'adjustment', 'warning', false),
            ])
            ->emptyStateHeading('Chưa có dữ liệu tồn kho')
            ->emptyStateDescription('Tồn kho sẽ xuất hiện sau khi sản phẩm có biến thể và được gán vào kho.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventories::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    private static function quantityAction(string $name, string $label, string $transactionType, string $color, bool $positiveOnly): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema([
                TextInput::make('quantity_delta')
                    ->label($positiveOnly ? 'Số lượng nhập' : 'Số lượng thay đổi')
                    ->helperText($positiveOnly ? 'Nhập số dương.' : 'Có thể nhập số âm để giảm tồn hoặc số dương để tăng tồn.')
                    ->numeric()
                    ->required()
                    ->minValue($positiveOnly ? 1 : -999999),
                TextInput::make('notes')
                    ->label('Ghi chú')
                    ->maxLength(500),
            ])
            ->authorize(fn (): bool => auth()->user()?->hasPermission($positiveOnly ? 'inventory.receive' : 'inventory.adjust') ?? false)
            ->action(function (Inventory $record, array $data) use ($transactionType, $positiveOnly): void {
                $delta = (int) $data['quantity_delta'];
                app(InventoryService::class)->adjust(
                    $record,
                    $positiveOnly ? abs($delta) : $delta,
                    $transactionType,
                    auth()->user(),
                    $data['notes'] ?? null,
                );
            });
    }
}
