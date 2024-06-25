<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Models\Staff;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inactivate')
                ->icon('heroicon-o-trash')
                ->color(Color::rgb('rgb(239, 68, 68)'))
                ->label('Inactivate')
                ->action(function (?Staff $record) {
                    $record->status_flag = 1;
                    $record->save();
                })
                ->requiresConfirmation()
                ->visible(function (?Staff $record) {
                    if ($record == null) {
                        return false;
                    }

                    return $record->status_flag == 0;
                }),
            Action::make('activate')
                ->icon('heroicon-o-arrow-path')
                ->color(Color::rgb('rgb(0, 162, 57)'))
                ->label('Activate')
                ->action(function (?Staff $record) {
                    $record->status_flag = 0;
                    $record->save();
                })
                ->requiresConfirmation()
                ->visible(function (?Staff $record) {
                    if ($record == null) {
                        return false;
                    }

                    return $record->status_flag == 1;
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Staff info updated';
    }
}
