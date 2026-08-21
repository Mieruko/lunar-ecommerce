<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;
    protected static ?string $navigationLabel = 'Sản phẩm';
    protected static ?string $modelLabel = 'sản phẩm';
    protected static ?string $pluralModelLabel = 'sản phẩm';
    protected static string|\UnitEnum|null $navigationGroup = 'Sản phẩm';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Quản lý sản phẩm')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Thông tin')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Thông tin cơ bản')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Tên sản phẩm')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state, Set $set, string $operation): void {
                                                if ($operation === 'create' && filled($state)) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            }),
                                        TextInput::make('slug')
                                            ->label('Đường dẫn (slug)')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(280),
                                        Select::make('product_type')
                                            ->label('Loại sản phẩm')
                                            ->options([
                                                'watch' => 'Đồng hồ',
                                                'jewelry' => 'Trang sức',
                                            ])
                                            ->required()
                                            ->live(),
                                        Select::make('status')
                                            ->label('Trạng thái')
                                            ->options(self::statusOptions())
                                            ->default('draft')
                                            ->required(),
                                        Select::make('brand_id')
                                            ->label('Thương hiệu')
                                            ->relationship('brand', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Select::make('category_id')
                                            ->label('Danh mục')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        TextInput::make('base_price_amount')
                                            ->label('Giá cơ sở')
                                            ->prefix('₫')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                        Toggle::make('is_featured')
                                            ->label('Sản phẩm nổi bật')
                                            ->default(false),
                                        TextInput::make('short_description')
                                            ->label('Mô tả ngắn')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Mô tả chi tiết')
                                            ->rows(6)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Biến thể')
                            ->icon(Heroicon::OutlinedSquares2x2)
                            ->schema([
                                Repeater::make('variants')
                                    ->relationship()
                                    ->label('Danh sách biến thể')
                                    ->schema([
                                        TextInput::make('sku')
                                            ->label('SKU')
                                            ->required()
                                            ->unique(ignoreRecord: true),
                                        TextInput::make('barcode')
                                            ->label('Barcode')
                                            ->unique(ignoreRecord: true),
                                        TextInput::make('name')
                                            ->label('Tên biến thể')
                                            ->placeholder('Ví dụ: EU 56 / Sterling silver'),
                                        Select::make('status')
                                            ->label('Trạng thái')
                                            ->options([
                                                'active' => 'Đang bán',
                                                'inactive' => 'Tạm ẩn',
                                            ])
                                            ->default('active')
                                            ->required(),
                                        TextInput::make('price_amount')
                                            ->label('Giá bán')
                                            ->prefix('₫')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),
                                        TextInput::make('compare_at_price_amount')
                                            ->label('Giá niêm yết')
                                            ->prefix('₫')
                                            ->numeric()
                                            ->minValue(0),
                                        TextInput::make('cost_amount')
                                            ->label('Giá vốn')
                                            ->prefix('₫')
                                            ->numeric()
                                            ->minValue(0),
                                        TextInput::make('weight_grams')
                                            ->label('Khối lượng')
                                            ->suffix('g')
                                            ->numeric()
                                            ->minValue(0),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->addActionLabel('Thêm biến thể')
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? $state['sku'] ?? 'Biến thể mới'),
                            ]),

                        Tab::make('Hình ảnh')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
                                Section::make('Thư viện ảnh')
                                    ->description('Có thể dùng đường dẫn ảnh đã có hoặc URL ảnh bên ngoài. Đánh dấu đúng một ảnh chính để hiển thị ngoài danh sách.')
                                    ->schema([
                                        Repeater::make('images')
                                            ->relationship()
                                            ->label('Hình ảnh sản phẩm')
                                            ->schema([
                                                TextInput::make('path')
                                                    ->label('Đường dẫn ảnh / URL')
                                                    ->required()
                                                    ->maxLength(1000)
                                                    ->columnSpanFull(),
                                                TextInput::make('alt_text')
                                                    ->label('Alt text')
                                                    ->maxLength(255),
                                                TextInput::make('sort_order')
                                                    ->label('Thứ tự')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0),
                                                Toggle::make('is_primary')
                                                    ->label('Ảnh chính')
                                                    ->default(false),
                                                TextInput::make('source_url')
                                                    ->label('Nguồn ảnh')
                                                    ->url()
                                                    ->maxLength(1000),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->addActionLabel('Thêm hình ảnh')
                                            ->orderColumn('sort_order')
                                            ->reorderable()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['alt_text'] ?? $state['path'] ?? 'Ảnh mới'),
                                    ]),
                            ]),

                        Tab::make('Thông số đồng hồ')
                            ->icon(Heroicon::OutlinedClock)
                            ->visible(fn (Get $get): bool => $get('product_type') === 'watch')
                            ->schema([
                                Section::make('Thông số kỹ thuật')
                                    ->relationship('watchDetail')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('movement')->label('Bộ máy'),
                                        TextInput::make('caliber')->label('Caliber'),
                                        TextInput::make('case_material')->label('Chất liệu vỏ'),
                                        TextInput::make('case_diameter_mm')->label('Đường kính vỏ')->suffix('mm')->numeric(),
                                        TextInput::make('case_thickness_mm')->label('Độ dày vỏ')->suffix('mm')->numeric(),
                                        TextInput::make('dial_color')->label('Màu mặt số'),
                                        TextInput::make('water_resistance_m')->label('Chống nước')->suffix('m')->numeric(),
                                        TextInput::make('crystal')->label('Kính'),
                                        TextInput::make('strap_material')->label('Chất liệu dây'),
                                        TextInput::make('strap_color')->label('Màu dây'),
                                        TextInput::make('clasp_type')->label('Loại khoá'),
                                        TextInput::make('power_reserve_hours')->label('Trữ cót')->suffix('giờ')->numeric(),
                                        TextInput::make('warranty_months')->label('Bảo hành')->suffix('tháng')->numeric(),
                                    ]),
                            ]),

                        Tab::make('Thông số trang sức')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->visible(fn (Get $get): bool => $get('product_type') === 'jewelry')
                            ->schema([
                                Section::make('Thông số kỹ thuật')
                                    ->relationship('jewelryDetail')
                                    ->columns(3)
                                    ->schema([
                                        Select::make('jewelry_type')
                                            ->label('Loại trang sức')
                                            ->required()
                                            ->options([
                                                'ring' => 'Nhẫn',
                                                'earrings' => 'Bông tai',
                                                'necklace' => 'Dây chuyền',
                                                'bracelet' => 'Vòng tay',
                                                'pendant' => 'Mặt dây',
                                                'other' => 'Khác',
                                            ]),
                                        Select::make('gender')
                                            ->label('Đối tượng')
                                            ->options([
                                                'women' => 'Nữ',
                                                'men' => 'Nam',
                                                'unisex' => 'Unisex',
                                            ])
                                            ->default('unisex'),
                                        TextInput::make('style')->label('Phong cách'),
                                        TextInput::make('ring_size_system')->label('Hệ size nhẫn'),
                                        TextInput::make('chain_length_mm')->label('Chiều dài dây chuyền')->suffix('mm')->numeric(),
                                        TextInput::make('bracelet_length_mm')->label('Chiều dài vòng')->suffix('mm')->numeric(),
                                        TextInput::make('dimensions')->label('Kích thước'),
                                        TextInput::make('total_weight_grams')->label('Khối lượng')->suffix('g')->numeric(),
                                        TextInput::make('plating')->label('Lớp mạ'),
                                        Textarea::make('care_instructions')
                                            ->label('Hướng dẫn bảo quản')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('SEO & nguồn')
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([
                                Section::make('Tối ưu tìm kiếm')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('SEO title')
                                            ->maxLength(255),
                                        TextInput::make('source_url')
                                            ->label('URL nguồn sản phẩm')
                                            ->url()
                                            ->maxLength(1000),
                                        Textarea::make('seo_description')
                                            ->label('SEO description')
                                            ->rows(4)
                                            ->maxLength(320)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['brand', 'category', 'primaryImage', 'variants.inventory']))
            ->columns([
                ImageColumn::make('primaryImage.path')
                    ->label('Ảnh')
                    ->square()
                    ->size(52)
                    ->checkFileExistence(false),
                TextColumn::make('name')
                    ->label('Sản phẩm')
                    ->description(fn (Product $record): string => $record->brand?->name ?? 'Chưa có thương hiệu')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('category.name')
                    ->label('Danh mục')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('product_type')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'watch' ? 'Đồng hồ' : 'Trang sức'),
                TextColumn::make('base_price_amount')
                    ->label('Giá')
                    ->money('VND', locale: 'vi')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('available_stock')
                    ->label('Tồn khả dụng')
                    ->state(fn (Product $record): int => $record->variants->sum(fn ($variant): int => $variant->inventory->sum(fn ($inventory): int => max(0, $inventory->quantity_on_hand - $inventory->quantity_reserved))))
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('is_featured')
                    ->label('Nổi bật')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(self::statusOptions()),
                SelectFilter::make('product_type')->label('Loại sản phẩm')->options([
                    'watch' => 'Đồng hồ',
                    'jewelry' => 'Trang sức',
                ]),
                SelectFilter::make('brand')->relationship('brand', 'name')->label('Thương hiệu')->searchable()->preload(),
                SelectFilter::make('category')->relationship('category', 'name')->label('Danh mục')->searchable()->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->label('Sửa'),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có sản phẩm')
            ->emptyStateDescription('Tạo sản phẩm đầu tiên để bắt đầu quản lý catalog.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    private static function statusOptions(): array
    {
        return [
            'draft' => 'Bản nháp',
            'active' => 'Đang bán',
            'archived' => 'Lưu trữ',
        ];
    }
}
