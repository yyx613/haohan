<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleTypeResource\Pages;
use App\Filament\Resources\VehicleTypeResource\RelationManagers;
use App\Models\VehicleType;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class VehicleTypeResource extends Resource
{
    protected static ?string $model = VehicleType::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Vehicle Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $pluralModelLabel = 'Vehicle Type';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->label('New vehicle type')
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                        return $rule->whereNull('deleted_at');
                    }),
                TextInput::make('seq_no')
                    ->label('Sequence')
                    ->default(0)
                    ->required()
                    ->numeric(),
                Fieldset::make('Vehicle')
                    ->schema([
                        Repeater::make('vehicles')
                            ->label('')
                            ->relationship(
                                name: 'vehicles'
                            )
                            ->columnSpanFull()
                            ->columns(2)
                            ->required(false)
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
                                Toggle::make('rented')
                                    ->inline(false)
                            ])
                            ->live(onBlur: true)
                            ->addActionLabel('Add new vehicle')
                            ->collapsed()
                            ->collapseAllAction(
                                function (\Filament\Forms\Components\Actions\Action $action) {
                                    $action->label('Collapse all');
                                }
                            )
                            ->expandAllAction(
                                function (\Filament\Forms\Components\Actions\Action $action) {
                                    $action->label('Expand all');
                                }
                            )
                            ->itemLabel(function (array $state) {
                                return $state['car_plate'] ?? null;
                            })
                            ->orderable()
                            ->orderColumn('seq_no')
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Vehicle type')
                    ->searchable(),
                TextInputColumn::make('seq_no')
                    ->label('Sequence')
            ])
            ->defaultSort('name', 'asc')
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
            ])->defaultSort('seq_no');
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
            'index' => Pages\ListVehicleTypes::route('/'),
            'create' => Pages\CreateVehicleType::route('/create'),
            'edit' => Pages\EditVehicleType::route('/{record}/edit'),
        ];
    }
}
