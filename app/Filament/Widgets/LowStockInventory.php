<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Models\Inventory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockInventory extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Cảnh báo tồn kho')
            ->description('Các SKU có tồn khả dụng thấp hơn hoặc bằng mức cảnh báo.')
            ->query(
                Inventory::query()
                    ->with(['warehouse', 'variant.product'])
                    ->whereRaw('(quantity_on_hand - quantity_reserved) <= reorder_level')
                    ->orderByRaw('(quantity_on_hand - quantity_reserved) asc')
            )
            ->columns([
                TextColumn::make('variant.product.name')->label('Sản phẩm')->weight('medium'),
                TextColumn::make('variant.sku')->label('SKU')->searchable(),
                TextColumn::make('warehouse.name')->label('Kho'),
                TextColumn::make('quantity_on_hand')->label('Tồn thực tế')->numeric()->alignCenter(),
                TextColumn::make('quantity_reserved')->label('Đang giữ')->numeric()->alignCenter(),
                TextColumn::make('available')
                    ->label('Khả dụng')
                    ->state(fn (Inventory $record): int => max(0, $record->quantity_on_hand - $record->quantity_reserved))
                    ->badge()
                    ->color(fn (int $state): string => $state <= 0 ? 'danger' : 'warning')
                    ->alignCenter(),
                TextColumn::make('reorder_level')->label('Mức cảnh báo')->numeric()->alignCenter(),
            ])
            ->recordUrl(fn (Inventory $record): string => InventoryResource::getUrl('index'))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission('inventory.view') ?? false;
    }
}
