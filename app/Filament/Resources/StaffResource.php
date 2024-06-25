<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Staff Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                        return $rule->whereNull('deleted_at');
                    }),
                Select::make('grouping_id')
                    ->native(false)
                    ->relationship(name: 'grouping', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('role_id')
                    ->native(false)
                    ->relationship(name: 'role', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('work_nature_id')
                    ->label('Nature of Work')
                    ->native(false)
                    ->relationship(name: 'work_nature', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('can_drive_lorry')
                    ->label('Lorry driver')
                    ->inline(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('work_nature.name')
                    ->label('Nature of Work')
                    ->searchable()
                    ->sortable(),

            ])
            ->groups([
                Group::make('grouping.id')
                    ->getTitleFromRecordUsing(function (Staff $record) {
                        return $record->grouping != null ? $record->grouping->name : "No group";
                    })
                    ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('grouping.id')
            ->defaultSort('seq_no', 'asc')
            ->filters([
                SelectFilter::make('grouping')
                    ->relationship('grouping', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('role')
                    ->relationship('role', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('work_nature')
                    ->relationship('work_nature', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('inactivate')
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
                    BulkAction::make('activate')
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
                ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
