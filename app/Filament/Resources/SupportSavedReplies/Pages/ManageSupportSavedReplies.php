<?php

namespace App\Filament\Resources\SupportSavedReplies\Pages;

use App\Filament\Resources\SupportSavedReplies\SupportSavedReplyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSupportSavedReplies extends ManageRecords
{
    protected static string $resource = SupportSavedReplyResource::class;

    public function getTitle(): string
    {
        return 'Câu trả lời mẫu';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tạo câu trả lời mẫu'),
        ];
    }
}
