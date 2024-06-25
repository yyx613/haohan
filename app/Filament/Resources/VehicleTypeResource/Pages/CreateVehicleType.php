<?php

namespace App\Filament\Resources\VehicleTypeResource\Pages;

use App\Filament\Resources\VehicleTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicleType extends CreateRecord
{
    protected static string $resource = VehicleTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Vehicle type created';
    }
}
