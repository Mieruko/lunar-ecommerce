<?php

namespace App\Filament\Resources\SupportConversations;

use App\Filament\Resources\Concerns\AdminResource;
use App\Filament\Resources\SupportConversations\Pages\ManageSupportConversations;
use App\Filament\Resources\SupportConversations\Pages\ViewSupportConversation;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Notifications\CustomerNotification;
use App\Services\AdminActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Validation\ValidationException;

class SupportConversationResource extends AdminResource
{
    protected static ?string $model = SupportConversation::class;

    protected static string $viewPermission = 'support.view';

    protected static ?string $managePermission = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Hộp thư chăm sóc';

    protected static ?string $modelLabel = 'hội thoại hỗ trợ';

    protected static ?string $pluralModelLabel = 'hội thoại hỗ trợ';

    protected static string|\UnitEnum|null $navigationGroup = 'Khách hàng & Hậu mãi';

    protected static ?int $navigationSort = 5;

    public const STATUSES = [
        SupportConversation::STATUS_UNASSIGNED => 'Chưa phân công',
        SupportConversation::STATUS_ASSIGNED => 'Đang xử lý',
        SupportConversation::STATUS_WAITING_CUSTOMER => 'Chờ khách phản hồi',
        SupportConversation::STATUS_RESOLVED => 'Đã giải quyết',
    ];

    public const PRIORITIES = [
        'low' => 'Thấp',
        'normal' => 'Bình thường',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', '!=', SupportConversation::STATUS_BOT)
            ->with(['user', 'assignee', 'order']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('last_message_at', 'desc')
            ->columns([
                TextColumn::make('uuid')
                    ->label('Mã')
                    ->formatStateUsing(fn (string $state): string => '#'.strtoupper(substr($state, 0, 8)))
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('subject')
                    ->label('Chủ đề')
                    ->placeholder('Yêu cầu hỗ trợ')
                    ->description(fn (SupportConversation $record): string => $record->category ?: 'Chưa phân loại')
                    ->searchable()
                    ->limit(42)
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Khách hàng')
                    ->placeholder('Khách vãng lai')
                    ->description(fn (SupportConversation $record): ?string => $record->user?->email)
                    ->searchable(),
                TextColumn::make('assignee.name')
                    ->label('Phụ trách')
                    ->placeholder('Chưa nhận')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => self::statusColor($state)),
                TextColumn::make('priority')
                    ->label('Ưu tiên')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::PRIORITIES[$state] ?? $state)
                    ->color(fn (string $state): string => self::priorityColor($state)),
                TextColumn::make('last_message_at')
                    ->label('Hoạt động gần nhất')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->multiple()
                    ->options(self::STATUSES),
                SelectFilter::make('assigned_to')
                    ->label('Nhân viên phụ trách')
                    ->relationship(
                        'assignee',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->whereHas('roles', fn (Builder $query): Builder => $query->where('is_staff', true)),
                    )
                    ->searchable()
                    ->preload(),
                Filter::make('unassigned')
                    ->label('Chưa có người nhận')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_to')),
                SelectFilter::make('priority')
                    ->label('Mức ưu tiên')
                    ->multiple()
                    ->options(self::PRIORITIES),
            ])
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->defaultPaginationPageOption(25)
            ->recordUrl(fn (SupportConversation $record): string => self::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Mở hội thoại'),
                Action::make('claim')
                    ->label('Nhận xử lý')
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->color('primary')
                    ->visible(fn (SupportConversation $record): bool => self::canClaim($record))
                    ->authorize(fn (): bool => self::canPerform('support.assign'))
                    ->action(fn (SupportConversation $record): SupportConversation => self::claim($record)),
                Action::make('resolve')
                    ->label('Giải quyết')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SupportConversation $record): bool => self::canResolve($record))
                    ->authorize(fn (): bool => self::canPerform('support.resolve'))
                    ->action(fn (SupportConversation $record): SupportConversation => self::resolve($record)),
            ])
            ->emptyStateIcon(Heroicon::OutlinedInbox)
            ->emptyStateHeading('Hộp thư đang trống')
            ->emptyStateDescription('Các cuộc hội thoại được chuyển cho nhân viên sẽ xuất hiện tại đây.');
    }

    public static function canPerform(string $permission): bool
    {
        return static::allowed($permission);
    }

    public static function canClaim(SupportConversation $conversation): bool
    {
        return self::canPerform('support.assign')
            && $conversation->status === SupportConversation::STATUS_UNASSIGNED
            && $conversation->assigned_to === null;
    }

    public static function canReply(SupportConversation $conversation): bool
    {
        if (! self::canPerform('support.reply')
            || $conversation->status === SupportConversation::STATUS_RESOLVED) {
            return false;
        }

        if ($conversation->assigned_to === auth()->id()) {
            return true;
        }

        return $conversation->assigned_to === null
            && $conversation->status === SupportConversation::STATUS_UNASSIGNED
            && self::canPerform('support.assign');
    }

    public static function canResolve(SupportConversation $conversation): bool
    {
        return self::canPerform('support.resolve')
            && $conversation->status !== SupportConversation::STATUS_RESOLVED;
    }

    public static function canReopen(SupportConversation $conversation): bool
    {
        return self::canPerform('support.resolve')
            && $conversation->status === SupportConversation::STATUS_RESOLVED;
    }

    public static function claim(SupportConversation $conversation): SupportConversation
    {
        self::ensureAuthorized('support.assign');

        $before = [
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
        ];

        $updated = SupportConversation::query()
            ->whereKey($conversation->getKey())
            ->where('status', SupportConversation::STATUS_UNASSIGNED)
            ->whereNull('assigned_to')
            ->update([
                'assigned_to' => auth()->id(),
                'status' => SupportConversation::STATUS_ASSIGNED,
                'staff_last_read_at' => now(),
            ]);

        if ($updated !== 1) {
            throw ValidationException::withMessages([
                'conversation' => 'Hội thoại đã được nhân viên khác nhận hoặc không còn chờ xử lý.',
            ]);
        }

        $conversation->refresh();
        self::audit('support.conversation_claimed', $conversation, $before, [
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
        ]);

        return $conversation;
    }

    public static function reply(SupportConversation $conversation, string $body): SupportMessage
    {
        self::ensureAuthorized('support.reply');
        $body = self::validatedBody($body);

        $message = DB::transaction(function () use ($conversation, $body): SupportMessage {
            $locked = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->getKey());
            self::ensureCanWrite($locked);

            $before = [
                'status' => $locked->status,
                'assigned_to' => $locked->assigned_to,
            ];

            $locked->update([
                'assigned_to' => $locked->assigned_to ?? auth()->id(),
                'status' => SupportConversation::STATUS_WAITING_CUSTOMER,
                'staff_last_read_at' => now(),
                'last_message_at' => now(),
            ]);

            $message = $locked->messages()->create([
                'sender_type' => SupportMessage::SENDER_STAFF,
                'sender_id' => auth()->id(),
                'body' => $body,
                'kind' => 'text',
            ]);

            self::audit('support.reply_sent', $locked, $before, [
                'status' => $locked->status,
                'assigned_to' => $locked->assigned_to,
                'message_id' => $message->id,
                'kind' => $message->kind,
            ]);

            return $message;
        });

        $conversation->refresh();
        self::notifyCustomer($conversation);

        return $message;
    }

    public static function addInternalNote(SupportConversation $conversation, string $body): SupportMessage
    {
        self::ensureAuthorized('support.reply');
        $body = self::validatedBody($body);

        $message = DB::transaction(function () use ($conversation, $body): SupportMessage {
            $locked = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->getKey());
            self::ensureCanWrite($locked);

            $message = $locked->messages()->create([
                'sender_type' => SupportMessage::SENDER_STAFF,
                'sender_id' => auth()->id(),
                'body' => $body,
                'kind' => 'internal_note',
                'read_at' => now(),
            ]);

            $locked->update([
                'assigned_to' => $locked->assigned_to ?? auth()->id(),
                'staff_last_read_at' => now(),
                'last_message_at' => now(),
            ]);

            self::audit('support.internal_note_added', $locked, null, [
                'message_id' => $message->id,
                'kind' => $message->kind,
            ]);

            return $message;
        });

        $conversation->refresh();

        return $message;
    }

    public static function setPriority(SupportConversation $conversation, string $priority): SupportConversation
    {
        self::ensureAuthorized('support.assign');

        if (! array_key_exists($priority, self::PRIORITIES)) {
            throw ValidationException::withMessages(['priority' => 'Mức ưu tiên không hợp lệ.']);
        }

        $before = ['priority' => $conversation->priority];
        $conversation->update(['priority' => $priority]);
        $conversation->refresh();
        self::audit('support.priority_changed', $conversation, $before, ['priority' => $priority]);

        return $conversation;
    }

    public static function resolve(SupportConversation $conversation): SupportConversation
    {
        self::ensureAuthorized('support.resolve');

        if ($conversation->status === SupportConversation::STATUS_RESOLVED) {
            return $conversation;
        }

        $before = ['status' => $conversation->status];
        $conversation->update([
            'status' => SupportConversation::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
        $conversation->refresh();
        self::audit('support.conversation_resolved', $conversation, $before, [
            'status' => $conversation->status,
        ]);

        return $conversation;
    }

    public static function reopen(SupportConversation $conversation): SupportConversation
    {
        self::ensureAuthorized('support.resolve');

        if ($conversation->status !== SupportConversation::STATUS_RESOLVED) {
            return $conversation;
        }

        $before = ['status' => $conversation->status];
        $status = $conversation->assigned_to
            ? SupportConversation::STATUS_ASSIGNED
            : SupportConversation::STATUS_UNASSIGNED;

        $conversation->update([
            'status' => $status,
            'resolved_at' => null,
        ]);
        $conversation->refresh();
        self::audit('support.conversation_reopened', $conversation, $before, [
            'status' => $conversation->status,
        ]);

        return $conversation;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            SupportConversation::STATUS_UNASSIGNED => 'warning',
            SupportConversation::STATUS_ASSIGNED => 'primary',
            SupportConversation::STATUS_WAITING_CUSTOMER => 'info',
            SupportConversation::STATUS_RESOLVED => 'success',
            default => 'gray',
        };
    }

    public static function priorityColor(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'low' => 'gray',
            default => 'info',
        };
    }

    public static function getNavigationBadge(): ?string
    {
        if (! SchemaFacade::hasTable('support_conversations')) {
            return null;
        }

        $count = SupportConversation::query()
            ->where('status', SupportConversation::STATUS_UNASSIGNED)
            ->whereNull('assigned_to')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
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
        return [
            'index' => ManageSupportConversations::route('/'),
            'view' => ViewSupportConversation::route('/{record}'),
        ];
    }

    private static function ensureAuthorized(string $permission): void
    {
        abort_unless(self::canPerform($permission), 403);
    }

    private static function ensureCanWrite(SupportConversation $conversation): void
    {
        if ($conversation->status === SupportConversation::STATUS_RESOLVED) {
            throw ValidationException::withMessages([
                'conversation' => 'Hãy mở lại hội thoại trước khi phản hồi.',
            ]);
        }

        if ($conversation->assigned_to === auth()->id()) {
            return;
        }

        if ($conversation->assigned_to !== null) {
            throw ValidationException::withMessages([
                'conversation' => 'Hội thoại đang do nhân viên khác phụ trách.',
            ]);
        }

        if ($conversation->status !== SupportConversation::STATUS_UNASSIGNED
            || ! self::canPerform('support.assign')) {
            throw ValidationException::withMessages([
                'conversation' => 'Bạn cần nhận hội thoại trước khi phản hồi.',
            ]);
        }
    }

    private static function validatedBody(string $body): string
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 4000) {
            throw ValidationException::withMessages([
                'body' => 'Nội dung phải có từ 1 đến 4.000 ký tự.',
            ]);
        }

        return $body;
    }

    private static function audit(
        string $action,
        SupportConversation $conversation,
        ?array $before,
        ?array $after,
    ): void {
        app(AdminActivityLogger::class)->log($action, $conversation, $before, $after);
    }

    private static function notifyCustomer(SupportConversation $conversation): void
    {
        $customer = $conversation->user()->first();

        if (! $customer) {
            return;
        }

        $customer->notify(new CustomerNotification(
            'support',
            'LUNAR Care đã phản hồi',
            'Yêu cầu hỗ trợ của bạn có phản hồi mới từ nhân viên chăm sóc khách hàng.',
            route('home', ['support' => 'chat']),
            ['conversation_id' => $conversation->id],
        ));
    }
}
