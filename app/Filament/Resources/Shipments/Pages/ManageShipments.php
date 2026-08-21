<?php
namespace App\Filament\Resources\Shipments\Pages;
use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
class ManageShipments extends ManageRecords { protected static string $resource = ShipmentResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label('Tạo vận đơn')]; } }
