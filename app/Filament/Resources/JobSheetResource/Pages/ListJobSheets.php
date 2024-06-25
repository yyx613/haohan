<?php

namespace App\Filament\Resources\JobSheetResource\Pages;

use App\Filament\Resources\JobSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobSheets extends ListRecords
{
    protected static string $resource = JobSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
