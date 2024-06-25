<?php

namespace App\Filament\Resources\GroupingResource\Pages;

use App\Filament\Resources\GroupingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGrouping extends EditRecord
{
    protected static string $resource = GroupingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
