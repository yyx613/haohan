<?php

namespace App\Filament\Resources\JobSheetResource\Pages;

use App\Filament\Resources\JobSheetResource;
use App\Models\JobSheet;
use DateTime;
use DateTimeZone;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditJobSheet extends EditRecord
{
    protected static string $resource = JobSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Action::make('publish')
                ->icon('heroicon-o-document-check')
                ->color(Color::rgb('rgb(0, 162, 57)'))
                ->label('Publish')
                ->action(function (JobSheet $record) {
                    $record->status_flag = 1;
                    $record->save();

                    $this->refreshFormData(['status_flag']);
                })
                ->requiresConfirmation()
                ->visible(function (JobSheet $record) {
                    return $record->status_flag == 0;
                }),
            Action::make('Download Pdf')
                ->icon('heroicon-o-arrow-down-tray')
                ->label('Download PDF (Latest changes)')
                ->url(function (JobSheet $record) {
                    return '/api/job_sheet/download_pdf?l=1&j=' . $record['id'];
                })
                ->visible(function (JobSheet $record) {
                    return $record->status_flag == 1;
                }),
            Action::make('Download Pdf')
                ->icon('heroicon-o-arrow-down-tray')
                ->label('Download PDF')
                ->url(function (JobSheet $record) {
                    return '/api/job_sheet/download_pdf?j=' . $record['id'];
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['version'] = (JobSheet::firstWhere('id', $data['id'])->version) + 1;
        $data['updated_at'] = new DateTime('now', new DateTimeZone('UTC'));
        $data['update_by'] = auth()->id();

        return $data;
    }
}
