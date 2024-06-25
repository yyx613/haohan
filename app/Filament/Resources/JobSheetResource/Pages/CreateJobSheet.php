<?php

namespace App\Filament\Resources\JobSheetResource\Pages;

use App\Filament\Resources\JobSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Log;
use Symfony\Component\Console\Output\ConsoleOutput;

class CreateJobSheet extends CreateRecord
{
    protected static string $resource = JobSheetResource::class;

    /* protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['version'] = 1;

        return $data;
    } */

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['update_by'] = auth()->id();
        $data['version'] = 0;

        return $data;
    }
}
