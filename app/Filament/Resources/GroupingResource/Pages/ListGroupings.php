<?php

namespace App\Filament\Resources\GroupingResource\Pages;

use App\Filament\Resources\GroupingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroupings extends ListRecords
{
    protected static string $resource = GroupingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
