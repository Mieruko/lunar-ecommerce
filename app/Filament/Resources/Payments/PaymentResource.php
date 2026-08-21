<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use App\Services\PaymentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends AdminResource
{
    protected static ?string $model = Payment::class;
    protected static string $viewPermission = 'payments.view';
    protected static ?string $managePermission = 'payments.refund';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $navigationLabel = 'Thanh toán';
    protected static string|\UnitEnum|null $navigationGroup = 'Bán hàng';
    protected static ?int $navigationSort = 30;
    private const STATUSES = ['pending' => 'Chờ thanh toán', 'authorized' => 'Đã xác thực', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'cancelled' => 'Đã huỷ', 'refunded' => 'Đã hoàn'];

    public static function form(Schema $schema): Schema { return $schema->components([]); }
    public static function table(Table $table): Table { return $table->modifyQueryUsing(fn (Builder $query) => $query->with(['order', 'refunds'])->latest())
        ->columns([
            TextColumn::make('order.order_number')->label('Mã đơn')->searchable()->weight('bold'),
            TextColumn::make('provider')->label('Cổng')->badge()->formatStateUsing(fn ($state) => strtoupper($state)),
            TextColumn::make('payment_method')->label('Phương thức')->formatStateUsing(fn ($state) => strtoupper($state)),
            TextColumn::make('amount')->label('Số tiền')->money('VND', locale: 'vi')->sortable(),
            TextColumn::make('refunds_sum_amount')->sum('refunds', 'amount')->label('Đã hoàn')->money('VND', locale: 'vi'),
            TextColumn::make('status')->label('Trạng thái')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state),
            TextColumn::make('paid_at')->label('Đã thu')->dateTime('d/m/Y H:i')->placeholder('—'),
        ])->filters([SelectFilter::make('status')->label('Trạng thái')->options(self::STATUSES), SelectFilter::make('provider')->label('Cổng')->options(['bank_transfer' => 'Chuyển khoản', 'cod' => 'COD', 'vnpay' => 'VNPAY', 'paypal' => 'PayPal'])])
        ->recordActions([
            Action::make('markPaid')->label('Xác nhận đã thu')->color('success')->requiresConfirmation()->visible(fn (Payment $record) => in_array($record->provider, ['cod', 'bank_transfer'], true) && $record->status !== 'paid')->authorize(fn () => static::allowed('payments.refund'))->action(fn (Payment $record) => app(PaymentService::class)->markPaid($record, auth()->user(), ['source' => $record->provider === 'cod' ? 'admin_cod_confirmation' : 'admin_bank_transfer_confirmation'])),
            Action::make('refund')->label('Ghi nhận hoàn tiền')->color('warning')->schema([TextInput::make('amount')->label('Số tiền hoàn (₫)')->numeric()->required()->minValue(1), Textarea::make('reason')->label('Lý do')->required()->maxLength(500)])->visible(fn (Payment $record) => in_array($record->status, ['paid', 'refunded'], true))->authorize(fn () => static::allowed('payments.refund'))->action(fn (Payment $record, array $data) => app(PaymentService::class)->recordRefund($record, (int) $data['amount'], $data['reason'], auth()->user())),
        ]); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function getPages(): array { return ['index' => ManagePayments::route('/')]; }
}
