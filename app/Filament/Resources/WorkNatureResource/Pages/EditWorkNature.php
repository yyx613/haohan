<?php

namespace App\Filament\Resources\WorkNatureResource\Pages;

use App\Filament\Resources\WorkNatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkNature extends EditRecord
{
    protected static string $resource = WorkNatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
