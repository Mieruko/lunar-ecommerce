<?php

namespace App\Filament\Resources\SupportFaqs\Pages;

use App\Filament\Resources\SupportFaqs\SupportFaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSupportFaqs extends ManageRecords
{
    protected static string $resource = SupportFaqResource::class;

    public function getTitle(): string
    {
        return 'Kiến thức chatbot';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Thêm câu hỏi'),
        ];
    }
}
