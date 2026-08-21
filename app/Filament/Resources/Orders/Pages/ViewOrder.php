<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\OrderNote;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Đơn hàng '.$this->getRecord()->order_number;
    }

    protected function getHeaderActions(): array
    {
        return [
            OrderResource::transitionAction('confirm', 'Xác nhận đơn', 'confirmed', 'warning'),
            OrderResource::transitionAction('prepare', 'Chuẩn bị hàng', 'preparing', 'info'),
            OrderResource::transitionAction('ship', 'Bàn giao vận chuyển', 'shipping', 'primary'),
            OrderResource::transitionAction('complete', 'Hoàn tất', 'completed', 'success'),
            OrderResource::transitionAction('cancel', 'Huỷ đơn', 'cancelled', 'danger'),
            Action::make('addNote')
                ->label('Thêm ghi chú')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->schema([
                    Textarea::make('body')
                        ->label('Ghi chú nội bộ')
                        ->rows(4)
                        ->required()
                        ->maxLength(2000),
                ])
                ->authorize(fn (): bool => auth()->user()?->hasPermission('orders.update_status') ?? false)
                ->action(function (array $data): void {
                    OrderNote::create([
                        'order_id' => $this->getRecord()->id,
                        'author_id' => auth()->id(),
                        'body' => $data['body'],
                        'is_internal' => true,
                    ]);

                    $this->getRecord()->refresh();
                }),
        ];
    }
}
