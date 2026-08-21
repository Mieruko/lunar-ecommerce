<?php
namespace App\Filament\Resources\ShippingZones\Pages;
use App\Filament\Resources\ShippingZones\ShippingZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
class ManageShippingZones extends ManageRecords { protected static string $resource = ShippingZoneResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label('Tạo khu vực')]; } }
