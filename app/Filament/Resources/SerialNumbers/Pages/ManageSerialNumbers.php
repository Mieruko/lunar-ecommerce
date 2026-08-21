<?php
namespace App\Filament\Resources\SerialNumbers\Pages;
use App\Filament\Resources\SerialNumbers\SerialNumberResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ManageRecords;
class ManageSerialNumbers extends ManageRecords { protected static string $resource = SerialNumberResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label('Thêm serial')]; } }
