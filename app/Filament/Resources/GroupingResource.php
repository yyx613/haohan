<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupingResource\Pages;
use App\Models\Grouping;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class GroupingResource extends Resource
{
    protected static ?string $model = Grouping::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Staff Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $pluralModelLabel = 'Grouping';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('seq_no')
                    ->label('Sequence')
                    ->default(0)
                    ->required()
                    ->numeric(),
                Fieldset::make('Staff')
                    ->schema([
                        Repeater::make('staffs')
                            ->label('')
                            ->relationship(
                                name: 'staffs'
                            )
                            ->columnSpanFull()
                            ->columns(2)
                            ->required(false)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                        return $rule->whereNull('deleted_at');
                                    }),
                                Select::make('role_id')
                                    ->native(false)
                                    ->relationship(name: 'role', titleAttribute: 'name')
                                    ->required(),
                                Select::make('work_nature_id')
                                    ->native(false)
                                    ->relationship(name: 'work_nature', titleAttribute: 'name')
                                    ->required(),
                                Toggle::make('can_drive_lorry')
                                    ->label('Lorry driver')
                                    ->inline(false)
                            ])
                            ->live(onBlur: true)
                            ->addActionLabel('Add new staff')
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
                                return $state['name'] ?? null;
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
                    ->searchable(),
                TextInputColumn::make('seq_no')
                    ->label('Sequence')
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
            'index' => Pages\ListGroupings::route('/'),
            'create' => Pages\CreateGrouping::route('/create'),
            'edit' => Pages\EditGrouping::route('/{record}/edit'),
        ];
    }
}
