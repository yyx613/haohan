<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkNatureResource\Pages;
use App\Models\WorkNature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkNatureResource extends Resource
{
    protected static ?string $model = WorkNature::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Staff Management';
    protected static ?int $navigationSort = 5;
    protected static ?string $pluralModelLabel = 'Nature of Work';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListWorkNatures::route('/'),
            'create' => Pages\CreateWorkNature::route('/create'),
            'edit' => Pages\EditWorkNature::route('/{record}/edit'),
        ];
    }
}
