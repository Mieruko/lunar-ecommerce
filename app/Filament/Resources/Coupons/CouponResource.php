<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CouponResource extends AdminResource
{
    protected static ?string $model = Coupon::class;
    protected static string $viewPermission = 'promotions.manage';
    protected static ?string $managePermission = 'promotions.manage';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;
    protected static ?string $navigationLabel = 'Mã giảm giá';
    protected static string|\UnitEnum|null $navigationGroup = 'Khuyến mãi';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Mã')->required()->unique(ignoreRecord: true)->maxLength(80)->live(onBlur: true)->afterStateUpdated(fn ($set, $state) => $set('code', Str::upper(trim($state))))->columnSpan(1),
            TextInput::make('name')->label('Tên chương trình')->required()->maxLength(255)->columnSpan(2),
            Select::make('discount_type')->label('Kiểu giảm')->options(['percent' => 'Phần trăm', 'fixed' => 'Số tiền (₫)'])->required(),
            TextInput::make('discount_value')->label('Giá trị giảm')->numeric()->minValue(1)->required(),
            TextInput::make('minimum_order_amount')->label('Đơn tối thiểu (₫)')->numeric()->default(0)->required(),
            TextInput::make('usage_limit')->label('Tổng lượt dùng')->numeric()->minValue(1)->nullable(),
            TextInput::make('per_customer_limit')->label('Lượt / khách')->numeric()->minValue(1)->nullable(),
            DateTimePicker::make('starts_at')->label('Bắt đầu')->seconds(false),
            DateTimePicker::make('ends_at')->label('Kết thúc')->seconds(false)->after('starts_at'),
            Toggle::make('is_active')->label('Đang hoạt động')->default(true)->required(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Mã')->searchable()->copyable()->weight('bold'),
            TextColumn::make('name')->label('Chương trình')->searchable(),
            TextColumn::make('discount_type')->label('Giảm')->formatStateUsing(fn ($state, Coupon $record) => $state === 'percent' ? $record->discount_value.'%' : number_format($record->discount_value, 0, ',', '.').' ₫'),
            TextColumn::make('minimum_order_amount')->label('Đơn tối thiểu')->money('VND', locale: 'vi'),
            TextColumn::make('usage_count')->label('Đã dùng')->suffix(fn (Coupon $record) => $record->usage_limit ? ' / '.$record->usage_limit : ''),
            TextColumn::make('ends_at')->label('Hết hạn')->dateTime('d/m/Y H:i')->placeholder('Không giới hạn')->sortable(),
            IconColumn::make('is_active')->label('Bật')->boolean(),
        ])->filters([SelectFilter::make('is_active')->label('Trạng thái')->options([1 => 'Đang bật', 0 => 'Đã tắt'])])
            ->recordActions([EditAction::make()->label('Sửa'), DeleteAction::make()->label('Xóa')])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array { return ['index' => ManageCoupons::route('/')]; }
}
