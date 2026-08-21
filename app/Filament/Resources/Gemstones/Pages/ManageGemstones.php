<?php
namespace App\Filament\Resources\Gemstones\Pages; use App\Filament\Resources\Gemstones\GemstoneResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ManageRecords; class ManageGemstones extends ManageRecords { protected static string $resource=GemstoneResource::class; protected function getHeaderActions(): array{return [CreateAction::make()->label('Thêm đá quý')];} }
