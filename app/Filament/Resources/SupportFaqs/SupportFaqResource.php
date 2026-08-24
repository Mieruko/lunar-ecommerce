<?php

namespace App\Filament\Resources\SupportFaqs;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\SupportFaqs\Pages\ManageSupportFaqs;
use App\Models\SupportFaq;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SupportFaqResource extends AdminResource
{
    protected static ?string $model = SupportFaq::class;

    protected static string $viewPermission = 'support.manage_knowledge';

    protected static ?string $managePermission = 'support.manage_knowledge';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Kiến thức chatbot';

    protected static ?string $modelLabel = 'câu hỏi thường gặp';

    protected static ?string $pluralModelLabel = 'câu hỏi thường gặp';

    protected static string|\UnitEnum|null $navigationGroup = 'Khách hàng & Hậu mãi';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('question')
                    ->label('Câu hỏi')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, string $operation): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Mã ổn định dùng để nhận diện câu trả lời.')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('category')
                    ->label('Nhóm chủ đề')
                    ->maxLength(80),
                Textarea::make('answer')
                    ->label('Nội dung trả lời')
                    ->rows(8)
                    ->required()
                    ->maxLength(10000)
                    ->columnSpanFull(),
                TagsInput::make('keywords')
                    ->label('Từ khóa nhận diện')
                    ->helperText('Nhấn Enter sau mỗi từ hoặc cụm từ.')
                    ->required(),
                TagsInput::make('suggestions')
                    ->label('Gợi ý tiếp theo')
                    ->helperText('Các câu hỏi gợi ý có thể hiện sau câu trả lời.'),
                TextInput::make('sort_order')
                    ->label('Thứ tự')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Cho chatbot sử dụng')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('question')
                    ->label('Câu hỏi')
                    ->searchable()
                    ->weight('bold')
                    ->limit(55)
                    ->wrap(),
                TextColumn::make('category')
                    ->label('Nhóm')
                    ->placeholder('Chung')
                    ->badge()
                    ->searchable(),
                TextColumn::make('keywords')
                    ->label('Từ khóa')
                    ->badge()
                    ->limitList(3)
                    ->expandableLimitedList(),
                TextColumn::make('sort_order')
                    ->label('Thứ tự')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Đang dùng')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
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
                    ->options(fn (): array => SupportFaq::query()
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
            ->emptyStateIcon(Heroicon::OutlinedBookOpen)
            ->emptyStateHeading('Chưa có kiến thức chatbot')
            ->emptyStateDescription('Thêm câu hỏi và câu trả lời để chatbot hỗ trợ khách chính xác hơn.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSupportFaqs::route('/'),
        ];
    }
}
