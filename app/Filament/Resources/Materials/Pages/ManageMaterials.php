<?php
namespace App\Filament\Resources\Materials\Pages; use App\Filament\Resources\Materials\MaterialResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ManageRecords; class ManageMaterials extends ManageRecords { protected static string $resource=MaterialResource::class; protected function getHeaderActions(): array{return [CreateAction::make()->label('Thêm chất liệu')];} }
