<?php

namespace App\Filament\Resources\SupportConversations\Pages;

use App\Filament\Resources\SupportConversations\SupportConversationResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSupportConversations extends ManageRecords
{
    protected static string $resource = SupportConversationResource::class;

    public function getTitle(): string
    {
        return 'Hộp thư chăm sóc khách hàng';
    }
}
