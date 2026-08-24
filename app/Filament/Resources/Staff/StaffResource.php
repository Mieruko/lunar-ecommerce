<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StaffResource extends AdminResource
{
    protected static ?string $model = User::class;

    protected static string $viewPermission = 'staff.manage';

    protected static ?string $managePermission = 'staff.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Nhân viên';

    protected static ?string $modelLabel = 'nhân viên';

    protected static ?string $pluralModelLabel = 'nhân viên';

    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('is_staff', true))
            ->with('roles');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
            TextInput::make('email')->label('Email đăng nhập')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->label('Điện thoại')->tel()->maxLength(30),
            Select::make('status')->label('Trạng thái')->options([
                'active' => 'Hoạt động',
                'blocked' => 'Đã khóa',
            ])->default('active')->required(),
            TextInput::make('password')
                ->label('Mật khẩu')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->minLength(8)
                ->same('password_confirmation')
                ->dehydrated(fn (?string $state): bool => filled($state)),
            TextInput::make('password_confirmation')
                ->label('Nhập lại mật khẩu')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(false),
            Select::make('roles')
                ->relationship('roles', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_staff', true))
                ->multiple()
                ->preload()
                ->searchable()
                ->label('Vai trò & quyền hạn')
                ->helperText('Chỉ các vai trò nhân viên có quyền truy cập trang quản trị mới được hiển thị.')
                ->required()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nhân viên')->searchable()->sortable()->weight('bold'),
                TextColumn::make('email')->label('Email')->searchable()->copyable(),
                TextColumn::make('phone')->label('Điện thoại')->searchable()->placeholder('—'),
                TextColumn::make('roles.name')->label('Vai trò')->badge(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Hoạt động' : 'Đã khóa')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(['active' => 'Hoạt động', 'blocked' => 'Đã khóa']),
                SelectFilter::make('roles')->label('Vai trò')
                    ->relationship('roles', 'name', fn (Builder $query): Builder => $query->where('is_staff', true))
                    ->multiple()
                    ->preload(),
            ])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->recordActions([EditAction::make()->label('Sửa')]);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageStaff::route('/')];
    }
}
