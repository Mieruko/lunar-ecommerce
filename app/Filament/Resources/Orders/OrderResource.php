<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Services\OrderStatusService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Đơn hàng';

    protected static ?string $modelLabel = 'đơn hàng';

    protected static ?string $pluralModelLabel = 'đơn hàng';

    protected static string|\UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Thông tin đơn hàng')
                    ->columnSpan(8)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('order_number')->label('Mã đơn')->weight('bold'),
                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                            ->color(fn (string $state): string => self::statusColor($state)),
                        TextEntry::make('payment_status')
                            ->label('Thanh toán')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => self::paymentStatusOptions()[$state] ?? $state)
                            ->color(fn (string $state): string => self::paymentStatusColor($state)),
                        TextEntry::make('placed_at')->label('Ngày đặt')->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Khách hàng')
                    ->columnSpan(4)
                    ->columns(1)
                    ->schema([
                        TextEntry::make('customer_name')->label('Họ tên')->weight('bold'),
                        TextEntry::make('customer_phone')->label('Số điện thoại')->copyable(),
                        TextEntry::make('customer_email')->label('Email')->copyable(),
                    ]),

                Section::make('Địa chỉ giao hàng')
                    ->columnSpan(12)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('shippingAddress.recipient_name')->label('Người nhận'),
                        TextEntry::make('shippingAddress.phone')->label('Điện thoại'),
                        TextEntry::make('shipping_address_full')
                            ->label('Địa chỉ')
                            ->state(function (Order $record): string {
                                $address = $record->shippingAddress;

                                if (! $address) {
                                    return 'Chưa có địa chỉ giao hàng';
                                }

                                return collect([
                                    $address->line_1,
                                    $address->line_2,
                                    $address->ward,
                                    $address->district,
                                    $address->province,
                                ])->filter()->implode(', ');
                            })
                            ->columnSpan(1),
                    ]),

                Section::make('Sản phẩm trong đơn')
                    ->columnSpan(12)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->table([
                                TableColumn::make('Sản phẩm'),
                                TableColumn::make('SKU'),
                                TableColumn::make('Biến thể'),
                                TableColumn::make('Đơn giá'),
                                TableColumn::make('SL'),
                                TableColumn::make('Thành tiền'),
                            ])
                            ->schema([
                                TextEntry::make('product_name')->label('Sản phẩm')->weight('medium'),
                                TextEntry::make('sku')->label('SKU'),
                                TextEntry::make('variant_name')->label('Biến thể')->placeholder('—'),
                                TextEntry::make('unit_price_amount')->label('Đơn giá')->money('VND', locale: 'vi'),
                                TextEntry::make('quantity')->label('SL')->numeric(),
                                TextEntry::make('total_amount')->label('Thành tiền')->money('VND', locale: 'vi')->weight('bold'),
                            ]),
                    ]),

                Section::make('Tổng tiền')
                    ->columnSpan(5)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subtotal_amount')->label('Tạm tính')->money('VND', locale: 'vi'),
                        TextEntry::make('discount_amount')->label('Giảm giá')->money('VND', locale: 'vi'),
                        TextEntry::make('shipping_amount')->label('Phí giao hàng')->money('VND', locale: 'vi'),
                        TextEntry::make('tax_amount')->label('Thuế')->money('VND', locale: 'vi'),
                        TextEntry::make('total_amount')->label('TỔNG CỘNG')->money('VND', locale: 'vi')->weight('bold')->columnSpanFull(),
                    ]),

                Section::make('Thanh toán')
                    ->columnSpan(7)
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->label('')
                            ->table([
                                TableColumn::make('Phương thức'),
                                TableColumn::make('Nhà cung cấp'),
                                TableColumn::make('Số tiền'),
                                TableColumn::make('Trạng thái'),
                                TableColumn::make('Thời gian thanh toán'),
                            ])
                            ->schema([
                                TextEntry::make('payment_method')->label('Phương thức')->formatStateUsing(fn (?string $state): string => strtoupper($state ?: '—')),
                                TextEntry::make('provider')->label('Nhà cung cấp')->formatStateUsing(fn (?string $state): string => strtoupper($state ?: '—')),
                                TextEntry::make('amount')->label('Số tiền')->money('VND', locale: 'vi'),
                                TextEntry::make('status')->label('Trạng thái')->badge(),
                                TextEntry::make('paid_at')->label('Thời gian thanh toán')->dateTime('d/m/Y H:i')->placeholder('—'),
                            ]),
                    ]),

                Section::make('Vận chuyển')
                    ->columnSpan(6)
                    ->schema([
                        RepeatableEntry::make('shipments')
                            ->label('')
                            ->schema([
                                TextEntry::make('carrier')->label('Đơn vị vận chuyển')->placeholder('Chưa cập nhật'),
                                TextEntry::make('tracking_number')->label('Mã vận đơn')->copyable()->placeholder('Chưa có'),
                                TextEntry::make('status')->label('Trạng thái')->badge(),
                                TextEntry::make('shipped_at')->label('Ngày gửi')->dateTime('d/m/Y H:i')->placeholder('—'),
                                TextEntry::make('delivered_at')->label('Ngày giao')->dateTime('d/m/Y H:i')->placeholder('—'),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Lịch sử trạng thái')
                    ->columnSpan(6)
                    ->schema([
                        RepeatableEntry::make('statusHistory')
                            ->label('')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Trạng thái')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state),
                                TextEntry::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i'),
                                TextEntry::make('changedBy.name')->label('Người thực hiện')->placeholder('Hệ thống'),
                                TextEntry::make('comment')->label('Ghi chú')->placeholder('—')->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Ghi chú')
                    ->columnSpan(12)
                    ->schema([
                        TextEntry::make('note')->label('Ghi chú của khách')->placeholder('Không có'),
                        TextEntry::make('cancellation_reason')
                            ->label('Lý do hủy đơn')
                            ->visible(fn (Order $record): bool => $record->status === 'cancelled')
                            ->color('danger')
                            ->columnSpanFull(),
                        RepeatableEntry::make('notes')
                            ->label('Ghi chú nội bộ')
                            ->schema([
                                TextEntry::make('body')->label('Nội dung'),
                                TextEntry::make('author.name')->label('Người ghi')->placeholder('Hệ thống'),
                                TextEntry::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['items', 'payments'])->latest('placed_at'))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Mã đơn')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Khách hàng')
                    ->description(fn (Order $record): string => $record->customer_phone)
                    ->searchable(['customer_name', 'customer_email', 'customer_phone']),
                TextColumn::make('items_count')
                    ->label('Sản phẩm')
                    ->counts('items')
                    ->suffix(' SP')
                    ->alignCenter(),
                TextColumn::make('total_amount')
                    ->label('Tổng tiền')
                    ->money('VND', locale: 'vi')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('payment_status')
                    ->label('Thanh toán')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::paymentStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => self::paymentStatusColor($state)),
                TextColumn::make('status')
                    ->label('Trạng thái đơn')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => self::statusColor($state)),
                TextColumn::make('placed_at')
                    ->label('Ngày đặt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái đơn')->options(self::statusOptions()),
                SelectFilter::make('payment_status')->label('Thanh toán')->options(self::paymentStatusOptions()),
                Filter::make('placed_at')
                    ->label('Ngày đặt hàng')
                    ->schema([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('placed_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('placed_at', '<=', $date))),
            ])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->defaultPaginationPageOption(25)
            ->recordUrl(fn (Order $record): string => self::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Xem'),
                self::transitionAction('confirm', 'Xác nhận', 'confirmed', 'warning'),
                self::transitionAction('prepare', 'Chuẩn bị', 'preparing', 'info'),
                self::transitionAction('ship', 'Bàn giao vận chuyển', 'shipping', 'primary'),
                self::transitionAction('complete', 'Hoàn tất', 'completed', 'success'),
                self::transitionAction('cancel', 'Huỷ đơn', 'cancelled', 'danger'),
            ])
            ->emptyStateHeading('Chưa có đơn hàng')
            ->emptyStateDescription('Đơn hàng mới sẽ xuất hiện tại đây để nhân viên xử lý.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
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

    public static function transitionAction(string $name, string $label, string $target, string $color): Action
    {
        $action = Action::make($name)
            ->label($label)
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (Order $record): bool => $target !== 'shipping' && in_array($target, app(OrderStatusService::class)->nextStatuses($record), true))
            ->authorize(fn (): bool => auth()->user()?->hasPermission('orders.update_status') ?? false);

        if ($target === 'cancelled') {
            $action->schema([
                Textarea::make('reason')
                    ->label('Lý do hủy đơn')
                    ->helperText('Lý do sẽ được lưu trong lịch sử đơn hàng và hiển thị cho quản lý.')
                    ->rows(4)
                    ->required()
                    ->maxLength(500),
            ]);
        }

        return $action->action(function (Order $record, array $data = []) use ($target): void {
            app(OrderStatusService::class)->transition(
                $record,
                $target,
                auth()->user(),
                $target === 'cancelled' ? ($data['reason'] ?? null) : null,
            );
            $record->refresh();
        });
    }

    public static function statusOptions(): array
    {
        return [
            'pending_confirmation' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'preparing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã huỷ',
            'returned' => 'Đã trả hàng',
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            'unpaid' => 'Chưa thanh toán',
            'pending' => 'Đang chờ',
            'paid' => 'Đã thanh toán',
            'partially_refunded' => 'Hoàn tiền một phần',
            'refunded' => 'Đã hoàn tiền',
            'failed' => 'Thất bại',
        ];
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            'pending_confirmation' => 'warning',
            'confirmed' => 'info',
            'preparing' => 'primary',
            'shipping' => 'info',
            'completed' => 'success',
            'cancelled', 'returned' => 'danger',
            default => 'gray',
        };
    }

    private static function paymentStatusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'pending' => 'warning',
            'unpaid' => 'gray',
            'partially_refunded', 'refunded' => 'info',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}
