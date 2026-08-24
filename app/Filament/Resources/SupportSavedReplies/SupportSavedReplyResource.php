<?php

namespace App\Filament\Resources\SupportSavedReplies;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\SupportSavedReplies\Pages\ManageSupportSavedReplies;
use App\Models\SupportSavedReply;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportSavedReplyResource extends AdminResource
{
    protected static ?string $model = SupportSavedReply::class;

    protected static string $viewPermission = 'support.manage_knowledge';

    protected static ?string $managePermission = 'support.manage_knowledge';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Câu trả lời mẫu';

    protected static ?string $modelLabel = 'câu trả lời mẫu';

    protected static ?string $pluralModelLabel = 'câu trả lời mẫu';

    protected static string|\UnitEnum|null $navigationGroup = 'Khách hàng & Hậu mãi';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Tên câu trả lời')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('shortcut')
                    ->label('Phím tắt')
                    ->helperText('Ví dụ: /bao-hanh')
                    ->maxLength(80)
                    ->unique(ignoreRecord: true),
                TextInput::make('category')
                    ->label('Nhóm chủ đề')
                    ->maxLength(80),
                Textarea::make('body')
                    ->label('Nội dung phản hồi')
                    ->rows(8)
                    ->required()
                    ->maxLength(4000)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Thứ tự')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Cho phép sử dụng')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Tên câu trả lời')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('shortcut')
                    ->label('Phím tắt')
                    ->placeholder('—')
                    ->badge()
                    ->copyable()
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Nhóm')
                    ->placeholder('Chung')
                    ->badge()
                    ->searchable(),
                TextColumn::make('body')
                    ->label('Nội dung')
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('sort_order')
                    ->label('Thứ tự')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Đang dùng')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Trạng thái')
                    ->options([
                        1 => 'Đang sử dụng',
                        0 => 'Đã tắt',
                    ]),
                SelectFilter::make('category')
                    ->label('Nhóm chủ đề')
                    ->options(fn (): array => SupportSavedReply::query()
                        ->whereNotNull('category')
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
            ])
            ->recordActions([
                EditAction::make()->label('Sửa'),
                DeleteAction::make()->label('Xóa'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->emptyStateHeading('Chưa có câu trả lời mẫu')
            ->emptyStateDescription('Tạo nội dung chuẩn để nhân viên phản hồi nhanh và nhất quán.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSupportSavedReplies::route('/'),
        ];
    }
}
