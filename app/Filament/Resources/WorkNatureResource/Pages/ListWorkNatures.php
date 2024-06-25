<?php

namespace App\Filament\Resources\WorkNatureResource\Pages;

use App\Filament\Resources\WorkNatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkNatures extends ListRecords
{
    protected static string $resource = WorkNatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
