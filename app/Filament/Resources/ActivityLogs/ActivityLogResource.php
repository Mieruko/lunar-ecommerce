<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Filament\Resources\Concerns\AdminResource;
use App\Models\AdminActivityLog;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLogResource extends AdminResource
{
    protected static ?string $model = AdminActivityLog::class;

    protected static string $viewPermission = 'staff.manage';

    protected static ?string $managePermission = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Nhật ký quản trị';

    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 30;

    private const ACTION_LABELS = [
        'payment.mark_paid' => 'Xác nhận đã thanh toán',
        'payment.refund_recorded' => 'Ghi nhận hoàn tiền',
        'shipment.created' => 'Tạo vận đơn',
        'shipment.status_changed' => 'Đổi trạng thái vận đơn',
        'return.status_changed' => 'Đổi trạng thái đổi trả',
    ];

    private const SUBJECT_LABELS = [
        'Payment' => 'Thanh toán',
        'Refund' => 'Hoàn tiền',
        'Shipment' => 'Vận đơn',
        'ReturnRequest' => 'Yêu cầu đổi trả',
        'Order' => 'Đơn hàng',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('actor'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Người thao tác')
                    ->placeholder('Hệ thống')
                    ->description(fn (AdminActivityLog $record): string => $record->actor?->email ?? 'Tự động từ cổng thanh toán/dịch vụ'),
                TextColumn::make('action')
                    ->label('Thao tác')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::ACTION_LABELS[$state] ?? $state)
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '—';
                        }
                        $class = class_basename($state);

                        return self::SUBJECT_LABELS[$class] ?? $class;
                    }),
                TextColumn::make('subject_id')
                    ->label('Mã bản ghi')
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—'),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageActivityLogs::route('/')];
    }
}
