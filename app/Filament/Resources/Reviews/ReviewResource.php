<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReviewResource extends AdminResource
{
    protected static ?string $model = Review::class;

    protected static string $viewPermission = 'reviews.moderate';

    protected static ?string $managePermission = 'reviews.moderate';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $navigationLabel = 'Đánh giá';

    protected static string|\UnitEnum|null $navigationGroup = 'Khách hàng & Hậu mãi';

    protected static ?int $navigationSort = 20;

    private const STATUSES = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->label('Trạng thái')->options(self::STATUSES)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.name')->label('Sản phẩm')->searchable()->limit(34)->weight('bold'),
                TextColumn::make('user.name')->label('Khách hàng')->searchable(),
                TextColumn::make('rating')->label('Điểm')->suffix(' ★')->sortable(),
                IconColumn::make('verified_purchase')->label('Đã mua')->boolean(),
                TextColumn::make('title')->label('Tiêu đề')->limit(35)->placeholder('Không có tiêu đề'),
                TextColumn::make('body')->label('Nội dung')->limit(55)->wrap(),
                TextColumn::make('status')->label('Trạng thái')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state),
                TextColumn::make('created_at')->label('Ngày gửi')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(self::STATUSES),
                SelectFilter::make('verified_purchase')->label('Nguồn đánh giá')->options([
                    1 => 'Đã xác thực mua hàng',
                    0 => 'Chưa xác thực',
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('Duyệt / Từ chối'),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageReviews::route('/')];
    }
}
