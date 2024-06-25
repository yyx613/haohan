<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobSheetResource\Pages;
use App\Models\Brand;
use App\Models\JobSheet;
use App\Models\JobSheetHistory;
use App\Models\JobSheetLeave;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamTask;
use App\Models\TeamTaskBrand;
use App\Models\TeamVehicle;
use App\Models\Vehicle;
use DateTime;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JobSheetResource extends Resource
{
    protected static ?string $model = JobSheet::class;
    protected static ?string $navigationGroup = 'Job Management';
    protected static ?int $navigationSort = 0;
    protected static ?string $pluralModelLabel = 'Job Sheet';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $thisResource = new JobSheetResource();

        return $form
            ->schema([
                Tabs::make('Form Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Basic info')
                            ->schema([
                                Hidden::make('id'),
                                Hidden::make('version'),
                                DatePicker::make('job_sheet_date')
                                    ->columnSpanFull()
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('status_flag')
                                    ->label('Status')
                                    ->options([
                                        0 => 'Draft',
                                        1 => 'Published'
                                    ])
                                    ->default(0)
                                    ->disabled()
                                    ->live()
                            ]),
                        Tab::make('Leave')
                            ->schema([
                                Select::make('annual_leaves')
                                    ->columnSpanFull()
                                    ->label(function (Get $get) {
                                        $al_label = 'Annual Leave';

                                        if ($get('annual_leaves') != null && count($get('annual_leaves')) > 0) {
                                            $al_label .= " (" . count($get('annual_leaves')) . ")";
                                        }

                                        return $al_label;
                                    })
                                    ->native(false)
                                    ->multiple()
                                    ->relationship(
                                        name: 'annual_leaves',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function (Builder $query, Get $get) use ($thisResource) {
                                            $query->whereNull('deleted_at')->where('status_flag', 0)->whereNotIn('id', $thisResource->get_annual_leave_excluded_staff($get));
                                        }
                                    )
                                    ->pivotData([
                                        'leave_type' => 0
                                    ])
                                    ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->nameWithRole)
                                    ->live()
                                    ->preload()
                                    ->beforeStateDehydrated(function (string $operation, Get $get, $state) {
                                        if ($operation == "edit") {
                                            if ($get('id') != null) {
                                                if ($get('status_flag') == 1) {
                                                    $existing = array_column(JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 0)->get()->toArray(), 'staff_id');

                                                    $new_entries = array_diff($state, $existing);

                                                    if (count($new_entries) > 0) {
                                                        $version = (JobSheet::firstWhere('id', $get('id'))->version) + 1;

                                                        foreach ($new_entries as $new_entry) {
                                                            JobSheetHistory::firstOrCreate(
                                                                [
                                                                    'job_sheet_id' => $get('id'),
                                                                    'history_type' => 0,
                                                                    'ref_id_1' => $new_entry,
                                                                    'ref_id_2' => 'AL',
                                                                    'version' => $version
                                                                ],
                                                                [
                                                                    'update_by' => auth()->id()
                                                                ]
                                                            );
                                                        }
                                                    }
                                                }

                                                JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 0)->delete();
                                            }
                                        }
                                    })
                                    ->suffixAction(
                                        \Filament\Forms\Components\Actions\Action::make('chooseMember_al')
                                            ->label('Select Staff')
                                            ->button()
                                            ->closeModalByClickingAway(false)
                                            ->form(function (Get $get) use ($thisResource) {
                                                $selected_members = $get('annual_leaves');

                                                return $thisResource->populate_staff_selection_component_list($selected_members, $thisResource->get_annual_leave_excluded_staff($get));
                                            })
                                            ->action(function ($data, Set $set) {
                                                $set('annual_leaves', $data['staff_list']);
                                            })
                                            ->modalHeading('Select Annual Leave Member')
                                            ->modalDescription('Unavailable staff will be hidden')
                                            ->modalSubmitActionLabel('Confirm Annual Leave Member')
                                            ->modalWidth(MaxWidth::ScreenExtraLarge)
                                    ),
                                Select::make('medical_leaves')
                                    ->columnSpanFull()
                                    ->label(function (Get $get) {
                                        $mc_label = 'Medical Leave';

                                        if ($get('medical_leaves') != null && count($get('medical_leaves')) > 0) {
                                            $mc_label .= " (" . count($get('medical_leaves')) . ")";
                                        }

                                        return $mc_label;
                                    })
                                    ->native(false)
                                    ->multiple()
                                    ->relationship(
                                        name: 'medical_leaves',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function (Builder $query, Get $get) use ($thisResource) {
                                            $query->whereNull('deleted_at')->where('status_flag', 0)->whereNotIn('id', $thisResource->get_mc_excluded_staff($get));
                                        }
                                    )
                                    ->pivotData([
                                        'leave_type' => 1
                                    ])
                                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                                        if ($record->role != null) {
                                            return "{$record->role->name} - {$record->name}";
                                        }

                                        return $record->name;
                                    })
                                    ->live()
                                    ->preload()
                                    ->beforeStateDehydrated(function (string $operation, Get $get, $state) {
                                        if ($operation == "edit") {
                                            if ($get('id') != null) {
                                                if ($get('status_flag') == 1) {
                                                    $existing = array_column(JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 1)->get()->toArray(), 'staff_id');

                                                    $new_entries = array_diff($state, $existing);

                                                    if (count($new_entries) > 0) {
                                                        $version = (JobSheet::firstWhere('id', $get('id'))->version) + 1;

                                                        foreach ($new_entries as $new_entry) {
                                                            JobSheetHistory::firstOrCreate(
                                                                [
                                                                    'job_sheet_id' => $get('id'),
                                                                    'history_type' => 0,
                                                                    'ref_id_1' => $new_entry,
                                                                    'ref_id_2' => 'MC',
                                                                    'version' => $version
                                                                ],
                                                                [
                                                                    'update_by' => auth()->id()
                                                                ]
                                                            );
                                                        }
                                                    }
                                                }

                                                JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 1)->delete();
                                            }
                                        }
                                    })
                                    ->suffixAction(
                                        \Filament\Forms\Components\Actions\Action::make('chooseMember_mc')
                                            ->label('Select Staff')
                                            ->button()
                                            ->closeModalByClickingAway(false)
                                            ->form(function (Get $get) use ($thisResource) {
                                                $selected_members = $get('medical_leaves');

                                                return $thisResource->populate_staff_selection_component_list($selected_members, $thisResource->get_mc_excluded_staff($get));
                                            })
                                            ->action(function ($data, Set $set) {
                                                $set('medical_leaves', $data['staff_list']);
                                            })
                                            ->modalHeading('Select MC Member')
                                            ->modalDescription('Unavailable staff will be hidden')
                                            ->modalSubmitActionLabel('Confirm MC Member')
                                            ->modalWidth(MaxWidth::ScreenExtraLarge)
                                    ),
                                Select::make('emergency_leaves')
                                    ->columnSpanFull()
                                    ->label(function (Get $get) {
                                        $el_label = 'Emergency Leave';

                                        if ($get('emergency_leaves') != null && count($get('emergency_leaves')) > 0) {
                                            $el_label .= " (" . count($get('emergency_leaves')) . ")";
                                        }

                                        return $el_label;
                                    })
                                    ->native(false)
                                    ->multiple()
                                    ->relationship(
                                        name: 'emergency_leaves',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function (Builder $query, Get $get) use ($thisResource) {
                                            $query->whereNull('deleted_at')->where('status_flag', 0)->whereNotIn('id', $thisResource->get_emergency_leave_excluded_staff($get));
                                        }
                                    )
                                    ->pivotData([
                                        'leave_type' => 2
                                    ])
                                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                                        if ($record->role != null) {
                                            return "{$record->role->name} - {$record->name}";
                                        }

                                        return $record->name;
                                    })
                                    ->live()
                                    ->preload()
                                    ->beforeStateDehydrated(function (string $operation, Get $get, $state) {
                                        if ($operation == "edit") {
                                            if ($get('id') != null) {
                                                if ($get('status_flag') == 1) {
                                                    $existing = array_column(JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 2)->get()->toArray(), 'staff_id');

                                                    $new_entries = array_diff($state, $existing);

                                                    if (count($new_entries) > 0) {
                                                        $version = (JobSheet::firstWhere('id', $get('id'))->version) + 1;

                                                        foreach ($new_entries as $new_entry) {
                                                            JobSheetHistory::firstOrCreate(
                                                                [
                                                                    'job_sheet_id' => $get('id'),
                                                                    'history_type' => 0,
                                                                    'ref_id_1' => $new_entry,
                                                                    'ref_id_2' => 'EL',
                                                                    'version' => $version
                                                                ],
                                                                [
                                                                    'update_by' => auth()->id()
                                                                ]
                                                            );
                                                        }
                                                    }
                                                }

                                                JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 2)->delete();
                                            }
                                        }
                                    })
                                    ->suffixAction(
                                        \Filament\Forms\Components\Actions\Action::make('chooseMember_el')
                                            ->label('Select Staff')
                                            ->button()
                                            ->closeModalByClickingAway(false)
                                            ->form(function (Get $get) use ($thisResource) {
                                                $selected_members = $get('emergency_leaves');

                                                return $thisResource->populate_staff_selection_component_list($selected_members, $thisResource->get_emergency_leave_excluded_staff($get));
                                            })
                                            ->action(function ($data, Set $set) {
                                                $set('emergency_leaves', $data['staff_list']);
                                            })
                                            ->modalHeading('Select Emergency Leave Member')
                                            ->modalDescription('Unavailable staff will be hidden')
                                            ->modalSubmitActionLabel('Confirm Emergency Leave Member')
                                            ->modalWidth(MaxWidth::ScreenExtraLarge)
                                    ),
                                Select::make('holidays')
                                    ->columnSpanFull()
                                    ->label(function (Get $get) {
                                        $el_label = 'Holiday';

                                        if ($get('holidays') != null && count($get('holidays')) > 0) {
                                            $el_label .= " (" . count($get('holidays')) . ")";
                                        }

                                        return $el_label;
                                    })
                                    ->native(false)
                                    ->multiple()
                                    ->relationship(
                                        name: 'holidays',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function (Builder $query, Get $get) use ($thisResource) {
                                            $query->whereNull('deleted_at')->where('status_flag', 0)->whereNotIn('id', $thisResource->get_holiday_excluded_staff($get));
                                        }
                                    )
                                    ->pivotData([
                                        'leave_type' => 3
                                    ])
                                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                                        if ($record->role != null) {
                                            return "{$record->role->name} - {$record->name}";
                                        }

                                        return $record->name;
                                    })
                                    ->live()
                                    ->preload()
                                    ->beforeStateDehydrated(function (string $operation, Get $get, $state) {
                                        if ($operation == "edit") {
                                            if ($get('id') != null) {
                                                if ($get('status_flag') == 1) {
                                                    $existing = array_column(JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 3)->get()->toArray(), 'staff_id');

                                                    $new_entries = array_diff($state, $existing);

                                                    if (count($new_entries) > 0) {
                                                        $version = (JobSheet::firstWhere('id', $get('id'))->version) + 1;

                                                        foreach ($new_entries as $new_entry) {
                                                            JobSheetHistory::firstOrCreate(
                                                                [
                                                                    'job_sheet_id' => $get('id'),
                                                                    'history_type' => 0,
                                                                    'ref_id_1' => $new_entry,
                                                                    'ref_id_2' => 'HOL',
                                                                    'version' => $version
                                                                ],
                                                                [
                                                                    'update_by' => auth()->id()
                                                                ]
                                                            );
                                                        }
                                                    }
                                                }

                                                JobSheetLeave::where('job_sheet_id', $get('id'))->where('leave_type', 3)->delete();
                                            }
                                        }
                                    })
                                    ->suffixAction(
                                        \Filament\Forms\Components\Actions\Action::make('chooseMember_hol')
                                            ->label('Select Staff')
                                            ->button()
                                            ->closeModalByClickingAway(false)
                                            ->form(function (Get $get) use ($thisResource) {
                                                $selected_members = $get('holidays');

                                                return $thisResource->populate_staff_selection_component_list($selected_members, $thisResource->get_holiday_excluded_staff($get));
                                            })
                                            ->action(function ($data, Set $set) {
                                                $set('holidays', $data['staff_list']);
                                            })
                                            ->modalHeading('Select Holiday Member')
                                            ->modalDescription('Unavailable staff will be hidden')
                                            ->modalSubmitActionLabel('Confirm Holiday Member')
                                            ->modalWidth(MaxWidth::ScreenExtraLarge)
                                    ),
                            ]),
                        Tab::make('Teams')
                            ->schema([
                                Repeater::make('teams')
                                    ->label('')
                                    ->columnSpanFull()
                                    ->columns(2)
                                    ->relationship()
                                    ->live(onBlur: true)
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                if ($operation == "edit") {
                                                    if ($get('../../id') != null) {
                                                        if ($get('../../status_flag') == 1) {
                                                            if ($record != null) {
                                                                $existing = Team::firstWhere('id', $record['id']);

                                                                if ($existing != null) {
                                                                    if ($existing['name'] != $state) {
                                                                        $version = (JobSheet::firstWhere('id', $get('../../id'))->version) + 1;

                                                                        JobSheetHistory::firstOrCreate(
                                                                            [
                                                                                'job_sheet_id' => $get('../../id'),
                                                                                'history_type' => 2,
                                                                                'ref_id_1' => $record['id'],
                                                                                'ref_id_2' => $record['id'],
                                                                                'version' => $version
                                                                            ],
                                                                            [
                                                                                'update_by' => auth()->id()
                                                                            ]
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }),
                                        TimePicker::make('time')
                                            ->seconds(false)
                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                if ($operation == "edit") {
                                                    if ($get('../../id') != null) {
                                                        if ($get('../../status_flag') == 1) {
                                                            if ($record != null) {
                                                                $existing = Team::firstWhere('id', $record['id']);

                                                                if ($existing != null) {
                                                                    $existing['time'] = (new DateTime($existing['time']))->format('H:i');

                                                                    if ($existing['time'] != $state) {
                                                                        $version = (JobSheet::firstWhere('id', $get('../../id'))->version) + 1;

                                                                        JobSheetHistory::firstOrCreate(
                                                                            [
                                                                                'job_sheet_id' => $get('../../id'),
                                                                                'history_type' => 5,
                                                                                'ref_id_1' => $record['id'],
                                                                                'ref_id_2' => $record['id'],
                                                                                'version' => $version
                                                                            ],
                                                                            [
                                                                                'update_by' => auth()->id()
                                                                            ]
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }),
                                        TextInput::make('overnight')
                                            ->default(0)
                                            ->numeric()
                                            ->inputMode('numeric')
                                            ->step(1)
                                            ->live(onBlur: true)
                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                if ($operation == "edit") {
                                                    if ($get('../../id') != null) {
                                                        if ($get('../../status_flag') == 1) {
                                                            if ($record != null) {
                                                                $existing = Team::firstWhere('id', $record['id']);

                                                                if ($existing != null) {
                                                                    if ($existing['overnight'] != $state) {
                                                                        $version = (JobSheet::firstWhere('id', $get('../../id'))->version) + 1;

                                                                        JobSheetHistory::firstOrCreate(
                                                                            [
                                                                                'job_sheet_id' => $get('../../id'),
                                                                                'history_type' => 3,
                                                                                'ref_id_1' => $record['id'],
                                                                                'ref_id_2' => $record['id'],
                                                                                'version' => $version
                                                                            ],
                                                                            [
                                                                                'update_by' => auth()->id()
                                                                            ]
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }),
                                        Toggle::make('nightshift')
                                            ->label('Night shift')
                                            ->inline(false)
                                            ->live(),
                                        Select::make('leaders')
                                            ->label(function (Get $get) {
                                                $leader_label = 'Leaders';

                                                if ($get('leaders') != null && count($get('leaders')) > 0) {
                                                    $leader_label .= " (" . count($get('leaders')) . ")";
                                                }

                                                return $leader_label;
                                            })
                                            ->columnSpanFull()
                                            ->multiple()
                                            ->relationship(
                                                name: 'leaders',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: function (Builder $query, Get $get, $state) use ($thisResource) {
                                                    $query->whereNull('deleted_at')->where('status_flag', 0)->whereNotIn('id', $thisResource->get_leader_excluded_staff($get, $state));
                                                }
                                            )
                                            ->pivotData([
                                                'team_position' => 1
                                            ])
                                            ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->nameWithRole)
                                            ->preload()
                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                if ($operation == "edit") {
                                                    if ($get('../../id') != null) {
                                                        if ($record != null) {
                                                            if ($get('../../status_flag') == 1) {
                                                                $existing = array_column(TeamMember::where('team_id', $record['id'])->get()->toArray(), 'staff_id');

                                                                $new_entries = array_diff($state, $existing);

                                                                if (count($new_entries) > 0) {
                                                                    $version = (JobSheet::firstWhere('id', $get('../../id'))->version) + 1;

                                                                    foreach ($new_entries as $new_entry) {
                                                                        JobSheetHistory::firstOrCreate(
                                                                            [
                                                                                'job_sheet_id' => $get('../../id'),
                                                                                'history_type' => 0,
                                                                                'ref_id_1' => $new_entry,
                                                                                'ref_id_2' => $record['id'],
                                                                                'version' => $version
                                                                            ],
                                                                            [
                                                                                'update_by' => auth()->id()
                                                                            ]
                                                                        );
                                                                    }
                                                                }
                                                            }

                                                            TeamMember::where('team_id', $record['id'])->where('team_position', 1)->delete();
                                                        }
                                                    }
                                                }
                                            })
                                            ->reactive()
                                            ->live(onBlur: true)
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('chooseLeader')
                                                    ->label('Select Leader')
                                                    ->button()
                                                    ->closeModalByClickingAway(false)
                                                    ->form(function (Get $get) use ($thisResource) {
                                                        $this_team_selected_leaders = $get('leaders');

                                                        return $thisResource->populate_staff_selection_component_list($this_team_selected_leaders, $thisResource->get_leader_excluded_staff($get, $this_team_selected_leaders));
                                                    })
                                                    ->action(function ($data, Set $set) {
                                                        $set('leaders', $data['staff_list']);
                                                    })
                                                    ->modalHeading('Select Team Leader')
                                                    ->modalSubmitActionLabel('Confirm Leader')
                                                    ->modalDescription('Unavailable staff will be hidden')
                                                    ->modalWidth(MaxWidth::ScreenExtraLarge)
                                            ),
                                        Select::make('team_members')
                                            ->label(function (Get $get) {
                                                $member_label = 'Members';

                                                if ($get('team_members') != null && count($get('team_members')) > 0) {
                                                    $member_label .= " (" . count($get('team_members')) . ")";
                                                }

                                                return $member_label;
                                            })
                                            ->columnSpanFull()
                                            ->multiple()
                                            ->relationship(
                                                name: 'team_members',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: function (Builder $query, Get $get, $state) use ($thisResource) {
                                                    $query->whereNull('deleted_at')->where('status_flag', 0)->whereNotIn('id', $thisResource->get_member_excluded_staff($get, $state));
                                                }
                                            )
                                            ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->nameWithRole)
                                            ->preload()
                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                if ($operation == "edit") {
                                                    if ($get('../../id') != null) {
                                                        if ($record != null) {
                                                            if ($get('../../status_flag') == 1) {
                                                                $existing = array_column(TeamMember::where('team_id', $record['id'])->get()->toArray(), 'staff_id');

                                                                $new_entries = array_diff($state, $existing);

                                                                if (count($new_entries) > 0) {
                                                                    $version = (JobSheet::firstWhere('id', $get('../../id'))->version) + 1;

                                                                    foreach ($new_entries as $new_entry) {
                                                                        JobSheetHistory::firstOrCreate(
                                                                            [
                                                                                'job_sheet_id' => $get('../../id'),
                                                                                'history_type' => 0,
                                                                                'ref_id_1' => $new_entry,
                                                                                'ref_id_2' => $record['id'],
                                                                                'version' => $version
                                                                            ],
                                                                            [
                                                                                'update_by' => auth()->id()
                                                                            ]
                                                                        );
                                                                    }
                                                                }
                                                            }

                                                            TeamMember::where('team_id', $record['id'])->where('team_position', 0)->delete();
                                                        }
                                                    }
                                                }
                                            })
                                            ->reactive()
                                            ->live()
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('chooseMember')
                                                    ->label('Select Member')
                                                    ->button()
                                                    ->closeModalByClickingAway(false)
                                                    ->form(function (Get $get) use ($thisResource) {
                                                        $this_team_selected_members = $get('team_members');

                                                        return $thisResource->populate_staff_selection_component_list($this_team_selected_members, $thisResource->get_member_excluded_staff($get, $this_team_selected_members));
                                                    })
                                                    ->action(function ($data, Set $set) {
                                                        $set('team_members', $data['staff_list']);
                                                    })
                                                    ->modalHeading('Select Team Member')
                                                    ->modalSubmitActionLabel('Confirm Member')
                                                    ->modalDescription('Unavailable staff will be hidden')
                                                    ->modalWidth(MaxWidth::ScreenExtraLarge)
                                            ),
                                        Select::make('team_vehicles')
                                            ->label(function (Get $get) {
                                                $vehicle_label = 'Vehicles';

                                                if ($get('team_vehicles') != null && count($get('team_vehicles')) > 0) {
                                                    $vehicle_label .= " (" . count($get('team_vehicles')) . ")";
                                                }

                                                return $vehicle_label;
                                            })
                                            ->live()
                                            ->columnSpanFull()
                                            ->multiple()
                                            ->relationship(
                                                name: 'team_vehicles',
                                                titleAttribute: 'car_plate',
                                                modifyQueryUsing: function (Builder $query, Get $get, $state) use ($thisResource) {
                                                    $selected_vehicle = $thisResource->get_excluded_vehicle($get, $state);

                                                    $query->whereNull('deleted_at')->whereNotIn('id', $selected_vehicle);
                                                }
                                            )
                                            ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->carPlateWithVehicleType)
                                            ->preload()
                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                if ($operation == "edit") {
                                                    if ($get('../../id') != null) {
                                                        if ($get('../../status_flag') == 1) {
                                                            if ($record != null) {
                                                                $existing = array_column(TeamVehicle::where('team_id', $record['id'])->get()->toArray(), 'vehicle_id');

                                                                $new_entries = array_diff($state, $existing);

                                                                if (count($new_entries) > 0) {
                                                                    $version = (JobSheet::firstWhere('id', $get('../../id'))->version) + 1;

                                                                    foreach ($new_entries as $new_entry) {
                                                                        JobSheetHistory::firstOrCreate(
                                                                            [
                                                                                'job_sheet_id' => $get('../../id'),
                                                                                'history_type' => 1,
                                                                                'ref_id_1' => $new_entry,
                                                                                'ref_id_2' => $record['id'],
                                                                                'version' => $version
                                                                            ],
                                                                            [
                                                                                'update_by' => auth()->id()
                                                                            ]
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            })
                                            ->suffixAction(
                                                \Filament\Forms\Components\Actions\Action::make('chooseVehicle')
                                                    ->label('Select vehicle')
                                                    ->button()
                                                    ->closeModalByClickingAway(false)
                                                    ->form(function (Get $get) use ($thisResource) {
                                                        $this_team_selected_vehicles = $get('team_vehicles');

                                                        return $thisResource->populate_vehicle_selection_component_list($this_team_selected_vehicles, $thisResource->get_excluded_vehicle($get, $this_team_selected_vehicles));
                                                    })
                                                    ->action(function ($data, Set $set) {
                                                        $set('team_vehicles', $data['vehicle_list']);
                                                    })
                                                    ->modalHeading('Select Team Vehicle')
                                                    ->modalSubmitActionLabel('Confirm Vehicle')
                                                    ->modalDescription('Unavailable vehicle will be hidden')
                                                    ->modalWidth(MaxWidth::ScreenExtraLarge)
                                            ),
                                        Fieldset::make('Tasks')
                                            ->schema([
                                                Repeater::make('team_tasks')
                                                    ->columnSpanFull()
                                                    ->label('')
                                                    ->relationship()
                                                    ->columns(2)
                                                    ->schema([
                                                        Select::make('task_id')
                                                            ->label('Task')
                                                            ->columnSpanFull()
                                                            ->required()
                                                            ->options(function () {
                                                                $task_option_list = Task::all()->pluck('name', 'id')->toArray();

                                                                $task_option_list[-1] = 'Others';

                                                                asort($task_option_list);

                                                                return $task_option_list;
                                                            })
                                                            ->searchable()
                                                            ->preload()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, Set $set) {
                                                                $set('brands', [-1]);
                                                            })
                                                            ->beforeStateDehydrated(function (Select $component, string $operation, ?Model $record, Get $get, $state) {
                                                                if ($operation == "edit") {
                                                                    if ($get('../../../../id') != null) {
                                                                        if ($get('../../../../status_flag') == 1) {
                                                                            if ($record != null) {
                                                                                $existing = TeamTask::firstWhere('id', $record['id']);

                                                                                if ($existing != null) {
                                                                                    if ($existing['task_id'] != ($state == -1 ? null : $state)) {
                                                                                        $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;

                                                                                        JobSheetHistory::firstOrCreate(
                                                                                            [
                                                                                                'job_sheet_id' => $get('../../../../id'),
                                                                                                'history_type' => 4,
                                                                                                'ref_id_1' => $record['id'],
                                                                                                'ref_id_2' => $existing['team_id'],
                                                                                                'version' => $version
                                                                                            ],
                                                                                            [
                                                                                                'update_by' => auth()->id()
                                                                                            ]
                                                                                        );
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }),
                                                        TextInput::make('name')
                                                            ->label('Specify task name')
                                                            ->columnSpanFull()
                                                            ->required(false)
                                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                                if ($operation == "edit") {
                                                                    if ($get('../../../../id') != null) {
                                                                        if ($get('../../../../status_flag') == 1) {
                                                                            if ($record != null) {
                                                                                $existing = TeamTask::firstWhere('id', $record['id']);

                                                                                if ($existing != null) {
                                                                                    if ($existing['name'] != $state) {
                                                                                        $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;

                                                                                        JobSheetHistory::firstOrCreate(
                                                                                            [
                                                                                                'job_sheet_id' => $get('../../../../id'),
                                                                                                'history_type' => 4,
                                                                                                'ref_id_1' => $record['id'],
                                                                                                'ref_id_2' => $existing['team_id'],
                                                                                                'version' => $version
                                                                                            ],
                                                                                            [
                                                                                                'update_by' => auth()->id()
                                                                                            ]
                                                                                        );
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            })
                                                            ->hidden(function (Get $get) {
                                                                $task_id = $get('task_id');

                                                                if (!isset ($task_id) || $task_id > 0) {
                                                                    return true;
                                                                }

                                                                return false;
                                                            }),
                                                        Select::make('brands')
                                                            ->columnSpanFull()
                                                            ->multiple()
                                                            ->relationship('brands', 'name')
                                                            ->options(function (Get $get) {
                                                                $brand_option_list = [];

                                                                $task_id = $get('task_id');

                                                                // if (isset ($task_id) && $task_id > 0) {
                                                                //     $brand_option_list = Task::find($task_id)->brands()->pluck('name', 'id')->toArray();
                                                                // }
                                                                $brand_option_list = Brand::pluck('name', 'id')->toArray();

                                                                $brand_option_list[-1] = 'Others';

                                                                asort($brand_option_list);

                                                                return $brand_option_list;
                                                            })
                                                            ->preload()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function (Select $component, $state) {

                                                                if ($state != null && count($state) > 1) {
                                                                    if (in_array(-1, $state)) {
                                                                        $component->state([-1]);
                                                                    }
                                                                }
                                                            })
                                                            ->beforeStateDehydrated(function (Select $component, string $operation, ?Model $record, Get $get, $state) {
                                                                $component->state(array_diff($state, [-1]));

                                                                if ($operation == "edit") {
                                                                    if ($get('../../../../id') != null) {
                                                                        if ($get('../../../../status_flag') == 1) {
                                                                            if ($record != null) {
                                                                                $this_team_task = TeamTask::with('brands')->firstWhere('id', $record['id']);

                                                                                $existing = $this_team_task->brands()->pluck('id')->toArray();

                                                                                if ($existing == null) {
                                                                                    $existing = [];
                                                                                }

                                                                                $state = array_diff($state, [-1]);
                                                                                $new_entries = array_diff($state, $existing);
                                                                                $removed_entries = array_diff($existing, $state);

                                                                                if (count($new_entries) > 0 || count($removed_entries) > 0) {
                                                                                    $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;

                                                                                    JobSheetHistory::firstOrCreate(
                                                                                        [
                                                                                            'job_sheet_id' => $get('../../../../id'),
                                                                                            'history_type' => 8,
                                                                                            'ref_id_1' => $record['id'],
                                                                                            'ref_id_2' => $record['team_id'],
                                                                                            'version' => $version
                                                                                        ],
                                                                                        [
                                                                                            'update_by' => auth()->id()
                                                                                        ]
                                                                                    );
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }),
                                                        TextInput::make('brand_name')
                                                            ->label('Specify brand name')
                                                            ->columnSpanFull()
                                                            ->required(false)
                                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                                if ($operation == "edit") {
                                                                    if ($get('../../../../id') != null) {
                                                                        if ($get('../../../../status_flag') == 1) {
                                                                            if ($record != null) {
                                                                                if (isset ($record['brands']) && in_array(-1, ($record['brands'])->toArray())) {
                                                                                    $existing = TeamTask::find($record['id']);

                                                                                    if ($existing != null) {
                                                                                        if ($existing['brand_name'] != $state) {
                                                                                            $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;

                                                                                            JobSheetHistory::firstOrCreate(
                                                                                                [
                                                                                                    'job_sheet_id' => $get('../../../../id'),
                                                                                                    'history_type' => 8,
                                                                                                    'ref_id_1' => $record['id'],
                                                                                                    'ref_id_2' => $existing['team_id'],
                                                                                                    'version' => $version
                                                                                                ],
                                                                                                [
                                                                                                    'update_by' => auth()->id()
                                                                                                ]
                                                                                            );
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            })
                                                            ->hidden(function (Get $get) {
                                                                $brands = $get('brands');

                                                                if (count($brands) == 0 || in_array(-1, $brands)) {
                                                                    return false;
                                                                }

                                                                return true;
                                                            }),
                                                        Select::make('location_id')
                                                            ->label('Location')
                                                            ->columnSpanFull()
                                                            ->options(function () {
                                                                $location_option_list = Location::all()->pluck('name', 'id')->toArray();

                                                                $location_option_list[-1] = 'Others';

                                                                asort($location_option_list);

                                                                return $location_option_list;
                                                            })
                                                            ->searchable()
                                                            ->preload()
                                                            ->live(onBlur: true)
                                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                                if ($operation == "edit") {
                                                                    if ($get('../../../../id') != null) {
                                                                        if ($get('../../../../status_flag') == 1) {
                                                                            if ($record != null) {
                                                                                $existing = TeamTask::find($record['id']);

                                                                                if ($existing != null) {
                                                                                    if ($existing['location_id'] != $state) {
                                                                                        $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;

                                                                                        JobSheetHistory::firstOrCreate(
                                                                                            [
                                                                                                'job_sheet_id' => $get('../../../../id'),
                                                                                                'history_type' => 6,
                                                                                                'ref_id_1' => $record['id'],
                                                                                                'ref_id_2' => $existing['team_id'],
                                                                                                'version' => $version
                                                                                            ],
                                                                                            [
                                                                                                'update_by' => auth()->id()
                                                                                            ]
                                                                                        );
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }),
                                                        TextInput::make('location_name')
                                                            ->label('Specify location name')
                                                            ->columnSpanFull()
                                                            ->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                                if ($operation == "edit") {
                                                                    if ($get('../../../../id') != null) {
                                                                        if ($get('../../../../status_flag') == 1) {
                                                                            if ($record != null) {
                                                                                $existing = TeamTask::firstWhere('id', $record['id']);

                                                                                if ($existing != null) {
                                                                                    if ($existing['location_name'] != $state) {
                                                                                        $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;

                                                                                        JobSheetHistory::firstOrCreate(
                                                                                            [
                                                                                                'job_sheet_id' => $get('../../../../id'),
                                                                                                'history_type' => 6,
                                                                                                'ref_id_1' => $record['id'],
                                                                                                'ref_id_2' => $existing['team_id'],
                                                                                                'version' => $version
                                                                                            ],
                                                                                            [
                                                                                                'update_by' => auth()->id()
                                                                                            ]
                                                                                        );
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            })
                                                            ->hidden(function (Get $get) {
                                                                $location_id = $get('location_id');

                                                                if (!isset ($location_id) || $location_id > 0) {
                                                                    return true;
                                                                }

                                                                return false;
                                                            }),
                                                        TextInput::make('no_of_booth')->default(0)->numeric()->inputMode('numeric')->step(1)->live(onBlur: true)->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                            if ($operation == "edit") {
                                                                if ($get('../../../../id') != null) {
                                                                    if ($get('../../../../status_flag') == 1) {
                                                                        if ($record != null) {
                                                                            $existing = TeamTask::firstWhere('id', $record['id']);
                                                                            if ($existing != null) {
                                                                                if ($existing['no_of_booth'] != $state) {
                                                                                    $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;
                                                                                    JobSheetHistory::firstOrCreate(
                                                                                        [
                                                                                            'job_sheet_id' => $get('../../../../id'),
                                                                                            'history_type' => 8,
                                                                                            'ref_id_1' => $record['id'],
                                                                                            'ref_id_2' => $existing['team_id'],
                                                                                            'version' => $version
                                                                                        ],
                                                                                        [
                                                                                            'update_by' => auth()->id()
                                                                                        ]
                                                                                    );
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }),
                                                        Textarea::make('remark')->beforeStateDehydrated(function (string $operation, ?Model $record, Get $get, $state) {
                                                            if ($operation == "edit") {
                                                                if ($get('../../../../id') != null) {
                                                                    if ($get('../../../../status_flag') == 1) {
                                                                        if ($record != null) {
                                                                            $existing = TeamTask::firstWhere('id', $record['id']);
                                                                            if ($existing != null) {
                                                                                if ($existing['remark'] != $state) {
                                                                                    $version = (JobSheet::firstWhere('id', $get('../../../../id'))->version) + 1;
                                                                                    JobSheetHistory::firstOrCreate(
                                                                                        [
                                                                                            'job_sheet_id' => $get('../../../../id'),
                                                                                            'history_type' => 7,
                                                                                            'ref_id_1' => $record['id'],
                                                                                            'ref_id_2' => $existing['team_id'],
                                                                                            'version' => $version
                                                                                        ],
                                                                                        [
                                                                                            'update_by' => auth()->id()
                                                                                        ]
                                                                                    );
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }),
                                                    ])
                                                    ->addActionLabel('Add new task')
                                                    ->collapsed()
                                                    ->collapseAllAction(
                                                        function (Actions\Action $action) {
                                                            $action->label('Collapse all tasks');
                                                        }
                                                    )
                                                    ->expandAllAction(
                                                        function (Actions\Action $action) {
                                                            $action->label('Expand all tasks');
                                                        }
                                                    )
                                                    ->itemLabel(function (array $state) {
                                                        $name = $state['name'];

                                                        if ($state['task_id'] != null && $state['task_id'] > 0) {
                                                            $name = Task::find($state['task_id'])?->name ?? '';
                                                        }

                                                        return $name;
                                                    })
                                                    ->orderColumn('task_no')
                                            ])
                                    ])
                                    ->collapsible()
                                    ->collapseAllAction(
                                        function (Actions\Action $action) {
                                            $action->label('Collapse all teams');
                                        }
                                    )
                                    ->expandAllAction(
                                        function (Actions\Action $action) {
                                            $action->label('Expand all teams');
                                        }
                                    )
                                    ->itemLabel(function (array $state) {
                                        $team_label = 'Team ' . ($state['name'] ?? '');
                                        $staff_count = 0;

                                        if ($state['leaders'] != null && count($state['leaders']) > 0) {
                                            $staff_count += count($state['leaders']);
                                        }

                                        if ($state['team_members'] != null && count($state['team_members']) > 0) {
                                            $staff_count += count($state['team_members']);
                                        }

                                        if ($staff_count > 0) {
                                            $team_label .= " (" . $staff_count . ")";
                                        }

                                        return $team_label;
                                    })
                                    ->addActionLabel('Add new team')
                                    ->orderColumn('team_no'),
                            ]),
                        Tab::make('Summary')
                            ->schema([
                                Fieldset::make('staff')
                                    ->label('Staff')
                                    ->columns(3)
                                    ->schema(
                                        function (Get $get) use ($thisResource) {
                                            $assigned_staff_list = [];

                                            if ($get('teams') != null) {
                                                foreach ($get('teams') as $team_id => $team) {
                                                    $assigned_staff_list = array_merge($assigned_staff_list, $team['leaders']);
                                                    $assigned_staff_list = array_merge($assigned_staff_list, $team['team_members']);
                                                }
                                            }

                                            $assigned_staff_list_count = count(array_unique($assigned_staff_list));
                                            $annual_leave_count = count($get('annual_leaves'));
                                            $medical_leave_count = count($get('medical_leaves'));
                                            $emergency_leave_count = count($get('emergency_leaves'));
                                            $holiday_count = count($get('holidays'));

                                            return $thisResource->populate_staff_summary($assigned_staff_list_count, $annual_leave_count, $medical_leave_count, $emergency_leave_count, $holiday_count);
                                        }
                                    ),
                                Fieldset::make('vehicle')
                                    ->label('Vehicle')
                                    ->columns(3)
                                    ->schema(
                                        function (Get $get) use ($thisResource) {

                                            $assigned_vehicle_list = [];

                                            if ($get('teams') != null) {
                                                foreach ($get('teams') as $team_id => $team) {
                                                    $assigned_vehicle_list = array_merge($assigned_vehicle_list, $team['team_vehicles']);
                                                }
                                            }

                                            return $thisResource->populate_vehicle_summary($assigned_vehicle_list);
                                        }
                                    )
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('job_sheet_date')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Download Pdf')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('Download PDF')
                    ->url(function (JobSheet $record) {
                        return '/api/job_sheet/download_pdf?j=' . $record['id'];
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    /* BulkAction::make('export')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label('Export PDF')
                        ->action(function () {
                            //
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion() */
                ]),
            ])
            ->defaultSort('job_sheet_date', 'desc');
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
            'index' => Pages\ListJobSheets::route('/'),
            'create' => Pages\CreateJobSheet::route('/create'),
            'edit' => Pages\EditJobSheet::route('/{record}/edit'),
        ];
    }

    public static function get_annual_leave_excluded_staff(Get $get)
    {
        $staff_to_exclude = [];

        if ($get('medical_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('medical_leaves'));
        }

        if ($get('emergency_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('emergency_leaves'));
        }

        if ($get('holidays') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('holidays'));
        }

        if ($get('teams') != null) {
            foreach ($get('teams') as $team_id => $team) {
                if ($team['team_members'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['team_members']);
                }

                if ($team['leaders'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['leaders']);
                }
            }
        }

        return array_unique($staff_to_exclude);
    }

    public static function get_mc_excluded_staff(Get $get)
    {
        $staff_to_exclude = [];

        if ($get('annual_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('annual_leaves'));
        }

        if ($get('emergency_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('emergency_leaves'));
        }

        if ($get('holidays') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('holidays'));
        }

        if ($get('teams') != null) {
            foreach ($get('teams') as $team_id => $team) {
                if ($team['team_members'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['team_members']);
                }

                if ($team['leaders'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['leaders']);
                }
            }
        }

        return array_unique($staff_to_exclude);
    }

    public static function get_emergency_leave_excluded_staff(Get $get)
    {
        $staff_to_exclude = [];

        if ($get('annual_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('annual_leaves'));
        }

        if ($get('medical_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('medical_leaves'));
        }

        if ($get('holidays') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('holidays'));
        }

        if ($get('teams') != null) {
            foreach ($get('teams') as $team_id => $team) {
                if ($team['team_members'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['team_members']);
                }

                if ($team['leaders'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['leaders']);
                }
            }
        }

        return array_unique($staff_to_exclude);
    }

    public static function get_holiday_excluded_staff(Get $get)
    {
        $staff_to_exclude = [];

        if ($get('annual_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('annual_leaves'));
        }

        if ($get('medical_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('medical_leaves'));
        }

        if ($get('emergency_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('emergency_leaves'));
        }

        if ($get('teams') != null) {
            foreach ($get('teams') as $team_id => $team) {
                if ($team['team_members'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['team_members']);
                }

                if ($team['leaders'] != null) {
                    $staff_to_exclude = array_merge($staff_to_exclude, $team['leaders']);
                }
            }
        }

        return array_unique($staff_to_exclude);
    }

    public static function get_leader_excluded_staff(Get $get, $selected_staff)
    {
        $staff_to_exclude = [];

        if ($get('../../annual_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../annual_leaves'));
        }

        if ($get('../../medical_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../medical_leaves'));
        }

        if ($get('../../emergency_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../emergency_leaves'));
        }

        if ($get('../../holidays') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../holidays'));
        }

        if ($get('../../teams') != null) {
            foreach ($get('../../teams') as $team_id => $team) {
                if ($get('nightshift') == $team['nightshift']) {
                    if (isset($team['leaders'])) {
                        $to_exclude = $team['leaders'];

                        if (isset($selected_staff)) {
                            $to_exclude = array_diff($team['leaders'], $selected_staff);
                        }

                        $staff_to_exclude = array_merge($staff_to_exclude, $to_exclude);
                    }

                    if (isset($team['team_members'])) {
                        $to_exclude = $team['team_members'];

                        $staff_to_exclude = array_merge($staff_to_exclude, $to_exclude);
                    }
                }
            }
        }

        return array_unique($staff_to_exclude);
    }

    public static function get_member_excluded_staff(Get $get, $selected_staff)
    {
        $staff_to_exclude = [];

        if ($get('../../annual_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../annual_leaves'));
        }

        if ($get('../../medical_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../medical_leaves'));
        }

        if ($get('../../emergency_leaves') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../emergency_leaves'));
        }

        if ($get('../../holidays') != null) {
            $staff_to_exclude = array_merge($staff_to_exclude, $get('../../holidays'));
        }

        if ($get('../../teams') != null) {
            foreach ($get('../../teams') as $team_id => $team) {
                if ($get('nightshift') == $team['nightshift']) {
                    if (isset($team['leaders'])) {
                        $to_exclude = $team['leaders'];

                        $staff_to_exclude = array_merge($staff_to_exclude, $to_exclude);
                    }

                    if (isset($team['team_members'])) {
                        $to_exclude = $team['team_members'];

                        if (isset($selected_staff)) {
                            $to_exclude = array_diff($team['team_members'], $selected_staff);
                        }

                        $staff_to_exclude = array_merge($staff_to_exclude, $to_exclude);
                    }
                }
            }
        }

        return array_unique($staff_to_exclude);
    }

    public static function get_excluded_vehicle(Get $get, $selected_vehicle)
    {
        $vehicle_to_exclude = [];

        if ($get('../../teams') != null) {
            foreach ($get('../../teams') as $team_id => $team) {
                if ($get('nightshift') == $team['nightshift']) {
                    if (isset($team['team_vehicles'])) {
                        $to_exclude = $team['team_vehicles'];

                        if (isset($selected_vehicle)) {
                            $to_exclude = array_diff($team['team_vehicles'], $selected_vehicle);
                        }

                        $vehicle_to_exclude = array_merge($vehicle_to_exclude, $to_exclude);
                    }
                }
            }
        }

        return array_unique($vehicle_to_exclude);
    }

    public static function populate_staff_selection_component_list($selected_staffs, $staff_to_exclude)
    {
        $staff_selection_component_list = [
            Select::make('staff_selection_group_by')
                ->label('Group by')
                ->options([
                    0 => 'Grouping',
                    1 => 'Role',
                    2 => 'Nature of Work',
                ])
                ->required()
                ->default(0)
                ->native(false)
                ->live()
                ->reactive()
        ];

        array_push($staff_selection_component_list, Fieldset::make('staff_list_fieldset')
            ->label('Staff list')
            ->schema(function (Get $get, $state) use ($selected_staffs, $staff_to_exclude) {
                $staff_selection_group_by = $get('staff_selection_group_by');

                $group_list = Staff::selectRaw(($staff_selection_group_by == 2 ? 'work_natures.id, work_natures.name' : ($staff_selection_group_by == 1 ? 'roles.id, roles.name' : 'groupings.id, groupings.name')) . ' AS name, count(1) AS staffs_count')
                    ->whereNotIn('staffs.id', $staff_to_exclude)
                    ->join($staff_selection_group_by == 2 ? 'work_natures' : ($staff_selection_group_by == 1 ? 'roles' : 'groupings'), $staff_selection_group_by == 2 ? 'work_nature_id' : ($staff_selection_group_by == 1 ? 'role_id' : 'grouping_id'), '=', $staff_selection_group_by == 2 ? 'work_natures.id' : ($staff_selection_group_by == 1 ? 'roles.id' : 'groupings.id'))
                    ->groupBy($staff_selection_group_by == 2 ? 'work_natures.id' : ($staff_selection_group_by == 1 ? 'roles.id' : 'groupings.id'), $staff_selection_group_by == 2 ? 'work_natures.name' : ($staff_selection_group_by == 1 ? 'roles.name' : 'groupings.name'));

                if ($staff_selection_group_by == 0) {
                    $group_list = $group_list->orderBy('groupings.seq_no');
                }

                $group_list = $group_list->get();

                $staff_group_list = [];

                foreach ($group_list as $group) {
                    if ($group->staffs_count > 0) {
                        array_push($staff_group_list, CheckboxList::make('staff_list')
                            ->label($group['name'])
                            ->options(function (Builder $query) use ($staff_selection_group_by, $group, $staff_to_exclude) {
                                return Staff::where($staff_selection_group_by == 2 ? 'work_nature_id' : ($staff_selection_group_by == 1 ? 'role_id' : 'grouping_id'), $group['id'])
                                    ->whereNotIn('id', $staff_to_exclude)
                                    ->whereNull('deleted_at')
                                    ->where('status_flag', 0)
                                    ->orderBy('seq_no')
                                    ->pluck('name', 'id');
                            })
                            ->default($selected_staffs)
                            ->columns(1));
                    }
                }

                return $staff_group_list;
            })
            ->columns([
                'sm' => 10,
                'md' => 10,
                'lg' => 10,
                'xl' => 10,
                '2xl' => 10,
            ]));

        return $staff_selection_component_list;
    }

    public static function populate_vehicle_selection_component_list($selected_vehicles, $vehicle_to_exclude)
    {
        $vehicle_selection_component_list = [];

        array_push($vehicle_selection_component_list, Fieldset::make('vehicle_list_fieldset')
            ->label('Vehicle list')
            ->schema(function (Get $get, $state) use ($selected_vehicles, $vehicle_to_exclude) {
                $group_list = Vehicle::selectRaw('vehicle_types.id, vehicle_types.name, count(1) as vehicle_count')
                    ->whereNotIn('vehicles.id', $vehicle_to_exclude)
                    ->join('vehicle_types', 'vehicle_type_id', 'vehicle_types.id')
                    ->groupBy('vehicle_types.id', 'vehicle_types.name')
                    ->orderBy('vehicle_types.seq_no')
                    ->get();

                $vehicle_group_list = [];

                foreach ($group_list as $group) {
                    if ($group->vehicle_count > 0) {
                        array_push($vehicle_group_list, CheckboxList::make('vehicle_list')
                            ->label($group['name'])
                            ->options(function (Builder $query) use ($group, $vehicle_to_exclude) {
                                return vehicle::where('vehicle_type_id', $group['id'])
                                    ->whereNotIn('id', $vehicle_to_exclude)
                                    ->whereNull('deleted_at')
                                    ->orderBy('seq_no')
                                    ->pluck('car_plate', 'id');
                            })
                            ->default($selected_vehicles)
                            ->columns(1));
                    }
                }

                return $vehicle_group_list;
            })
            ->columns([
                'sm' => 10,
                'md' => 10,
                'lg' => 10,
                'xl' => 10,
                '2xl' => 10,
            ]));

        return $vehicle_selection_component_list;
    }

    public static function populate_staff_summary($assigned_staff_list_count, $annual_leave_count, $medical_leave_count, $emergency_leave_count, $holiday_count)
    {
        $total_staff_count = Staff::all()->count();
        $total_unassigned_staff_count = $total_staff_count - $assigned_staff_list_count - $annual_leave_count - $medical_leave_count - $emergency_leave_count - $holiday_count;

        $staff_summary_component_list = [];

        array_push($staff_summary_component_list, Placeholder::make('total_assigned_staff')
            ->label('Total staff assigned to team')
            ->columnSpanFull()
            ->content("{$assigned_staff_list_count}/{$total_staff_count}"));

        array_push($staff_summary_component_list, Placeholder::make('total_annual_leave')
            ->label('Total annual leave')
            ->columnSpan(1)
            ->content("{$annual_leave_count}"));

        array_push($staff_summary_component_list, Placeholder::make('total_medical_leave')
            ->label('Total medical leave')
            ->columnSpan(1)
            ->content("{$medical_leave_count}"));

        array_push($staff_summary_component_list, Placeholder::make('total_emergency_leave')
            ->label('Total emergency leave')
            ->columnSpan(1)
            ->content("{$emergency_leave_count}"));

        array_push($staff_summary_component_list, Placeholder::make('total_holiday')
            ->label('Total holiday')
            ->columnSpan(1)
            ->content("{$holiday_count}"));

        array_push($staff_summary_component_list, Placeholder::make('total_unassigned_staff')
            ->label('Total unassigned staff')
            ->columnSpan(1)
            ->content("{$total_unassigned_staff_count}"));

        return $staff_summary_component_list;
    }

    public static function populate_vehicle_summary($assigned_vehicle_list)
    {
        $total_vehicle_count = Vehicle::where('rented', 0)->count();

        $assigned_vehicle_count_rented = Vehicle::where('rented', 1)
            ->whereIn('vehicles.id', $assigned_vehicle_list)->get()->pluck('id');

        $assigned_vehicle_count_without_rented = Vehicle::where('rented', 0)
            ->whereIn('vehicles.id', $assigned_vehicle_list)->get()->pluck('id');

        $total_unassigned_vehicle = $total_vehicle_count - count($assigned_vehicle_count_without_rented);

        $vehicle_info_component_list = [];

        $group_list = Vehicle::selectRaw('vehicle_types.id, vehicle_types.name, count(1) as vehicle_count')
            ->where('rented', 0)
            ->join('vehicle_types', 'vehicle_type_id', 'vehicle_types.id')
            ->groupBy('vehicle_types.id', 'vehicle_types.name')
            ->get();

        $assigned_group_list = Vehicle::selectRaw('vehicle_types.id, vehicle_types.name, count(1) as vehicle_count')
            ->where('rented', 0)
            ->whereIn('vehicles.id', $assigned_vehicle_list)
            ->join('vehicle_types', 'vehicle_type_id', 'vehicle_types.id')
            ->groupBy('vehicle_types.id', 'vehicle_types.name')
            ->get();

        foreach ($group_list as $group) {
            $assigned_no = 0;
            $assigned_group = $assigned_group_list->firstWhere('id', $group['id']);

            if ($assigned_group != null) {
                $assigned_no = $assigned_group['vehicle_count'];
            }

            array_push(
                $vehicle_info_component_list,
                Placeholder::make($group['name'])
                    ->label($group['name'])
                    ->content("{$assigned_no}/{$group['vehicle_count']}")
            );
        }

        array_push(
            $vehicle_info_component_list,
            Placeholder::make('rented')
                ->label('Rental')
                ->content(count($assigned_vehicle_count_rented))
        );

        array_push(
            $vehicle_info_component_list,
            Placeholder::make('total_unassigned_vehicle')
                ->label('Total unassigned vehicle')
                ->columnSpanFull()
                ->content("{$total_unassigned_vehicle}/{$total_vehicle_count}")
        );

        return $vehicle_info_component_list;
    }
}
