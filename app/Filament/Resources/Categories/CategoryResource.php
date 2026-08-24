<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Danh mục';

    protected static string|\UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Danh mục cha')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->default(null),
                TextInput::make('name')
                    ->label('Tên danh mục')
                    ->required(),
                TextInput::make('slug')
                    ->label('Đường dẫn')
                    ->required(),
                Textarea::make('description')
                    ->label('Mô tả')
                    ->default(null)
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Ảnh đại diện')
                    ->disk(config('filesystems.default'))
                    ->directory('catalog/categories')
                    ->image(),
                Toggle::make('is_active')
                    ->label('Đang hiển thị')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Thứ tự')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['products', 'children']))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('parent.name')->label('Danh mục cha')->sortable()->placeholder('Danh mục gốc'),
                TextColumn::make('name')
                    ->label('Tên danh mục')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('products_count')->label('Sản phẩm')->numeric()->sortable(),
                TextColumn::make('children_count')->label('Danh mục con')->numeric()->sortable(),
                ImageColumn::make('image_path')->label('Ảnh'),
                IconColumn::make('is_active')
                    ->label('Hiển thị')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Thứ tự')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('parent_id')->label('Danh mục cha')->relationship('parent', 'name')->preload()->searchable(),
                TernaryFilter::make('is_active')->label('Trạng thái hiển thị')->trueLabel('Đang hiển thị')->falseLabel('Đang ẩn'),
            ])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
