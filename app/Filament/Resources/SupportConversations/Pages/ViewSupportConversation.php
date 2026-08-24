<?php

namespace App\Filament\Resources\SupportConversations\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\SupportConversations\SupportConversationResource;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportSavedReply;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

class ViewSupportConversation extends ViewRecord
{
    protected static string $resource = SupportConversationResource::class;

    protected string $view = 'filament.resources.support-conversations.pages.view-support-conversation';

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->markStaffRead();
    }

    public function getTitle(): string
    {
        $conversation = $this->conversation();

        return $conversation->subject
            ?: 'Hội thoại #'.strtoupper(substr($conversation->uuid, 0, 8));
    }

    public function getSubheading(): ?string
    {
        $conversation = $this->conversation();

        return 'Cập nhật gần nhất '.($conversation->last_message_at?->diffForHumans() ?? 'chưa xác định');
    }

    public function refreshConversation(): void
    {
        $this->getRecord()->refresh();
        $this->markStaffRead();
    }

    public function conversation(): SupportConversation
    {
        /** @var SupportConversation $conversation */
        $conversation = $this->getRecord();
        $conversation->load([
            'user',
            'assignee',
            'order',
            'messages' => fn ($query) => $query
                ->with('sender')
                ->orderBy('id'),
        ]);

        return $conversation;
    }

    public function orderUrl(): ?string
    {
        $order = $this->conversation()->order;

        if (! $order || ! OrderResource::canView($order)) {
            return null;
        }

        return OrderResource::getUrl('view', ['record' => $order]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('claim')
                ->label('Nhận xử lý')
                ->icon(Heroicon::OutlinedHandRaised)
                ->color('primary')
                ->visible(fn (): bool => SupportConversationResource::canClaim($this->conversation()))
                ->authorize(fn (): bool => SupportConversationResource::canPerform('support.assign'))
                ->action(function (): void {
                    SupportConversationResource::claim($this->conversation());
                    $this->refreshConversation();
                    $this->success('Đã nhận hội thoại');
                }),
            Action::make('reply')
                ->label('Phản hồi khách')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->visible(fn (): bool => SupportConversationResource::canReply($this->conversation()))
                ->authorize(fn (): bool => SupportConversationResource::canPerform('support.reply'))
                ->schema([
                    Select::make('saved_reply_id')
                        ->label('Câu trả lời mẫu')
                        ->placeholder('Chọn nếu muốn điền nhanh')
                        ->options(fn (): array => SupportSavedReply::query()
                            ->active()
                            ->pluck('title', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if (! $state) {
                                return;
                            }

                            $body = SupportSavedReply::query()
                                ->active()
                                ->whereKey($state)
                                ->value('body');

                            if ($body) {
                                $set('body', $body);
                            }
                        }),
                    Select::make('product_ids')
                        ->label('Sản phẩm tư vấn')
                        ->helperText('Tìm theo tên, slug hoặc SKU. Khách sẽ thấy ảnh, giá và nút mở sản phẩm trong chat.')
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => SupportConversationResource::searchProducts($search))
                        ->getOptionLabelsUsing(fn (array $values): array => SupportConversationResource::productLabels($values))
                        ->maxItems(3),
                    Textarea::make('body')
                        ->label('Nội dung phản hồi')
                        ->helperText('Khách hàng sẽ nhận thông báo, nhưng nội dung không được ghi vào audit log.')
                        ->rows(7)
                        ->required()
                        ->maxLength(4000),
                ])
                ->modalSubmitActionLabel('Gửi phản hồi')
                ->action(function (array $data): void {
                    SupportConversationResource::reply(
                        $this->conversation(),
                        $data['body'],
                        $data['product_ids'] ?? [],
                    );
                    $this->refreshConversation();
                    $this->success('Đã gửi phản hồi');
                }),
            Action::make('internalNote')
                ->label('Ghi chú nội bộ')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->visible(fn (): bool => SupportConversationResource::canReply($this->conversation()))
                ->authorize(fn (): bool => SupportConversationResource::canPerform('support.reply'))
                ->schema([
                    Textarea::make('body')
                        ->label('Ghi chú cho đội CSKH')
                        ->helperText('Ghi chú này không bao giờ hiển thị cho khách hàng.')
                        ->rows(5)
                        ->required()
                        ->maxLength(4000),
                ])
                ->modalSubmitActionLabel('Lưu ghi chú')
                ->action(function (array $data): void {
                    SupportConversationResource::addInternalNote($this->conversation(), $data['body']);
                    $this->refreshConversation();
                    $this->success('Đã thêm ghi chú nội bộ');
                }),
            Action::make('setPriority')
                ->label('Mức ưu tiên')
                ->icon(Heroicon::OutlinedFlag)
                ->color('gray')
                ->visible(fn (): bool => SupportConversationResource::canPerform('support.assign')
                    && $this->conversation()->status !== SupportConversation::STATUS_RESOLVED)
                ->authorize(fn (): bool => SupportConversationResource::canPerform('support.assign'))
                ->schema([
                    Select::make('priority')
                        ->label('Mức ưu tiên')
                        ->options(SupportConversationResource::PRIORITIES)
                        ->default(fn (): string => $this->conversation()->priority)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    SupportConversationResource::setPriority($this->conversation(), $data['priority']);
                    $this->refreshConversation();
                    $this->success('Đã cập nhật mức ưu tiên');
                }),
            Action::make('resolve')
                ->label('Đánh dấu đã giải quyết')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => SupportConversationResource::canResolve($this->conversation()))
                ->authorize(fn (): bool => SupportConversationResource::canPerform('support.resolve'))
                ->action(function (): void {
                    SupportConversationResource::resolve($this->conversation());
                    $this->refreshConversation();
                    $this->success('Đã đóng hội thoại');
                }),
            Action::make('reopen')
                ->label('Mở lại hội thoại')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => SupportConversationResource::canReopen($this->conversation()))
                ->authorize(fn (): bool => SupportConversationResource::canPerform('support.resolve'))
                ->action(function (): void {
                    SupportConversationResource::reopen($this->conversation());
                    $this->refreshConversation();
                    $this->success('Đã mở lại hội thoại');
                }),
        ];
    }

    private function markStaffRead(): void
    {
        /** @var SupportConversation $conversation */
        $conversation = $this->getRecord();
        $unreadQuery = $conversation->messages()
            ->where('sender_type', SupportMessage::SENDER_CUSTOMER);

        if ($conversation->staff_last_read_at) {
            $unreadQuery->where('created_at', '>', $conversation->staff_last_read_at);
        }

        if (! $unreadQuery->exists()) {
            return;
        }

        $readAt = now();
        $conversation->messages()
            ->where('sender_type', SupportMessage::SENDER_CUSTOMER)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);
        $conversation->update(['staff_last_read_at' => $readAt]);
        $conversation->refresh();
    }

    private function success(string $title): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->send();
    }
}
