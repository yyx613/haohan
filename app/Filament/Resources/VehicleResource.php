<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Vehicle Management';
    protected static ?int $navigationSort = 0;
    protected static ?string $pluralModelLabel = 'Vehicle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('car_plate')
                    ->label('Name')
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                        return $rule->whereNull('deleted_at');
                    })
                    ->dehydrateStateUsing(function (string $state) {
                        return preg_replace('/\s+/', '', strtoupper($state));
                    }),
                Select::make('vehicle_type_id')
                    ->native(false)
                    ->relationship(name: 'vehicle_type', titleAttribute: 'name')
                    ->required(),
                Toggle::make('rented')
                    ->inline(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('car_plate')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('vehicle_type.name')
                    ->sortable()
                    ->searchable()
            ])
            ->defaultSort('car_plate', 'asc')
            ->filters([
                SelectFilter::make('vehicle_type')
                    ->label('Vehicle type')
                    ->relationship('vehicle_type', 'name')
                    ->multiple()
                    ->preload()
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
