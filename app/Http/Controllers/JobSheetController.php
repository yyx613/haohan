<?php

namespace App\Http\Controllers;

use App\Models\JobSheet;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\Vehicle;
use Mccarlosen\LaravelMpdf\LaravelMpdf;
use PDF;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;

class JobSheetController extends Controller
{

    public static function downloadPDF(Request $request)
    {
        $thisController = new JobSheetController();

        $job_sheet_id = is_numeric($request->input('j')) ? $request->input('j') : null;
        $only_latest = ($request->input('l') ?? 0) == 1;

        if (!isset($job_sheet_id)) {
            return response()->json([
                'message' => 'Invalid Job Sheet ID',
            ], 400);
        }

        $job_sheet = JobSheet::with(['job_sheet_histories', 'annual_leaves.grouping', 'medical_leaves.grouping', 'emergency_leaves.grouping', 'update_by_user'])->firstWhere('id', $job_sheet_id);

        if (!isset($job_sheet)) {
            return response()->json([
                'message' => 'Job Sheet not found',
            ], 404);
        }

        $latest_version_team = [];

        if (count($job_sheet->job_sheet_histories) == 0) {
            $only_latest = false;
        }

        if ($only_latest) {
            $latest_version = $job_sheet->job_sheet_histories->max('version');

            $latest_version_team = $job_sheet->job_sheet_histories->where('version', $latest_version)->pluck('ref_id_2')->toArray();
        }

        $is_draft = $job_sheet->status_flag == 0;

        $teams = Team::where('job_sheet_id', $job_sheet_id)
            ->with(['leaders.grouping', 'team_members.grouping', 'team_vehicles.vehicle_type', 'team_tasks'])
            ->orderBy('team_no')
            ->get();

        $team_table_list_string = "";

        $staff_id_list = [];
        $vehicle_id_list = [];

        foreach ($teams as $team) {
            $staff_id_list = array_merge($staff_id_list, $team->leaders->pluck('id')->toArray());
            $staff_id_list = array_merge($staff_id_list, $team->team_members->pluck('id')->toArray());
            $vehicle_id_list = array_merge($vehicle_id_list, $team->team_vehicles->pluck('id')->toArray());
        }

        $staff_id_double = array_keys(array_filter(array_count_values($staff_id_list), function ($count) {
            return $count > 1;
        }));

        $vehicle_id_double = array_keys(array_filter(array_count_values($vehicle_id_list), function ($count) {
            return $count > 1;
        }));

        foreach ($teams as $team) {
            $is_new_team = count($job_sheet->job_sheet_histories->where('history_type', 9)->where('ref_id_2', $team->id)) > 0;

            if (count($latest_version_team) == 0 || in_array($team->id, $latest_version_team)) {
                $staff_count = 0;

                // Leader 
                $leader_list_string = "<tr style='border-right:1px solid black;'>";

                $leader_list = $team->leaders;
                $leader_list_count = count($leader_list);

                if ($leader_list_count > 0) {
                    $staff_count += $leader_list_count;

                    $leader_list = $leader_list->sortBy([
                        fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                        fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                    ])->values()->all();

                    $row_count = ceil($leader_list_count / 8);

                    $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid white;" : "border-bottom:1px solid #d9d9d9;";

                    $leader_list_string .= "<td colspan='2' class='table_detail_key thin_bottom' style='border:1px solid black;{$multirow_border_bottom} '><span style=''>Leader : </span><span style='float: right;'>{$leader_list_count}</span></td>";

                    for ($row = 0; $row < $row_count; $row++) {
                        $not_last_row = $row != ($row_count - 1);

                        $thin_top = '';

                        if ($row > 0) {
                            $mutirow_not_last = $not_last_row ? "border-bottom:1px solid white;" : "";

                            $leader_list_string .= "<tr><td colspan='2' class='table_detail_key thin_bottom' style='border-top:1px solid white;border-left:1px solid black;border-right:1px solid black;{$mutirow_not_last}'></td>";

                            $thin_top = 'thin_top';
                        }

                        for ($col = 0; $col < 8; $col++) {
                            $leader_name = '';
                            $leader_name_chinese = '';
                            $leader_editted = '';
                            $leader_double = '';
                            $can_drive_lorry = '';

                            $cur_count = $col + ($row * 8);

                            $last_col_border_right = $col == 7 ? " border-right:1px solid black; " : "";

                            if ($cur_count < $leader_list_count) {
                                if ($leader_list[$cur_count] != null) {
                                    $this_leader = $leader_list[$cur_count];

                                    $leader_name = $this_leader->name;

                                    if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $this_leader->id)->where('ref_id_2', $team->id)) > 0 || $is_new_team) {
                                        $leader_editted = 'editted';
                                    }

                                    $leader_double = in_array($this_leader->id, $staff_id_double) ? 'double' : '';

                                    $can_drive_lorry = $this_leader->can_drive_lorry ? 'can_drive_lorry' : '';

                                    $leader_name_chinese = $thisController->containChineseCharacters($leader_name);
                                }
                            }

                            $leader_list_string .= "<td class='{$leader_editted} table_detail_value {$thin_top} thin_bottom'  style='{$last_col_border_right}'><span class='{$leader_double} {$can_drive_lorry} {$leader_name_chinese}'>{$leader_name}</span></td>";
                        }

                        if ($not_last_row) {
                            $leader_list_string .= "</tr>";
                        }
                    }
                } else {
                    $leader_list_string .= "<td colspan='2' class='table_detail_key thin_bottom' style='border:1px solid black;border-bottom:1px solid #d9d9d9;'>Leader :</td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom' style='border:1px solid black;border-bottom:1px solid #d9d9d9;border-left:1px solid white;'></td>";
                }

                $leader_list_string .= "</tr>";

                // Team Member
                $team_member_list_string = "<tr style='border-right:1px solid black;'>";

                $team_member_list = $team->team_members;
                $team_member_list_count = count($team_member_list);

                if ($team_member_list_count > 0) {
                    $staff_count += $team_member_list_count;

                    $team_member_list = $team_member_list->sortBy([
                        fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                        fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                    ])->values()->all();

                    $row_count = ceil($team_member_list_count / 8);

                    $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid white;" : "border-bottom:1px solid black;";

                    $team_member_list_string .= "<td colspan='2' class='table_detail_key thin_top' style='border:1px solid black;{$multirow_border_bottom} border-top:1px solid #d9d9d9;'><span style=''>Member : </span><span style='float: right;'>{$team_member_list_count}</span></td>";

                    for ($row = 0; $row < $row_count; $row++) {
                        $not_last_row = $row != ($row_count - 1);

                        if ($row > 0) {
                            $mutirow_not_last = $not_last_row ? "border-bottom:1px solid white;" : "border-bottom:1px solid black;";

                            $team_member_list_string .= "<tr><td colspan='2' class='table_detail_key' style='border:1px solid black;border-top:1px solid white;{$mutirow_not_last}'></td>";
                        }

                        $border_bottom = $not_last_row ? 'border-bottom: 1px solid #d9d9d9;' : 'border-bottom: 1px solid black;';

                        for ($col = 0; $col < 8; $col++) {
                            $member_name = '';
                            $member_name_chinese = '';
                            $member_editted = '';
                            $member_double = '';
                            $can_drive_lorry = '';

                            $cur_count = $col + ($row * 8);

                            $last_col_border_right = $col == 7 ? " border-right:1px solid black; " : "";

                            if ($cur_count < $team_member_list_count) {
                                if ($team_member_list[$cur_count] != null) {
                                    $this_member = $team_member_list[$cur_count];

                                    $member_name = $this_member->name;

                                    if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $this_member->id)->where('ref_id_2', $team->id)) > 0 || $is_new_team) {
                                        $member_editted = 'editted';
                                    }

                                    $member_double = in_array($this_member->id, $staff_id_double) ? 'double' : '';

                                    $can_drive_lorry = $this_member->can_drive_lorry ? 'can_drive_lorry' : '';

                                    $member_name_chinese = $thisController->containChineseCharacters($member_name);
                                }
                            }

                            $team_member_list_string .= "<td class='{$member_editted} table_detail_value thin_top' style='{$last_col_border_right} {$border_bottom}'><span class='{$member_double} {$can_drive_lorry} {$member_name_chinese}'>{$member_name}</span></td>";
                        }

                        if ($row != ($row_count - 1)) {
                            $team_member_list_string .= "</tr>";
                        }
                    }
                } else {
                    $team_member_list_string .= "<td colspan='2' class='table_detail_key' style='border:1px solid black;border-top:1px solid #d9d9d9;'>Member :</td>
                    <td class='table_detail_value' style='border-bottom:1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom:1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom:1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom:1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom:1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom:1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom:1px solid black;'></td>
                    <td class='table_detail_value' style='border:1px solid black;border-top:1px solid #d9d9d9;border-left:1px solid white;'></td>";
                }

                $team_member_list_string .= "</tr>";

                // Team Vehicle
                $team_vehicle_list_string = "<tr style='border-right:1px solid black;'>";

                $team_vehicle_list = $team->team_vehicles;
                $team_vehicle_list_count = count($team->team_vehicles);

                if ($team_vehicle_list_count > 0) {
                    $row_count = ceil($team_vehicle_list_count / 8);

                    $team_vehicle_list = $team_vehicle_list->sortBy([
                        fn($a, $b) => $a['vehicle_type']['seq_no'] <=> $b['vehicle_type']['seq_no'],
                        fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                    ])->values()->all();

                    $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid white;" : "border-bottom:1px solid black;";

                    $team_vehicle_list_string .= "<td colspan='2' class='table_detail_key' style='border:1px solid black; {$multirow_border_bottom}'><span style=''>Vehicle : </span><span style='float: right;'>{$team_vehicle_list_count}</span></td>";

                    for ($row = 0; $row < $row_count; $row++) {
                        $not_last_row = $row != ($row_count - 1);

                        $thin_top = '';

                        if ($row > 0) {
                            $mutirow_not_last = $not_last_row ? "border-bottom:1px solid white;" : "border-bottom:1px solid black;";

                            $team_vehicle_list_string .= "<tr><td colspan='2' class='table_detail_key' style='border:1px solid black;border-top:1px solid white;{$mutirow_not_last}'></td>";

                            $thin_top = 'thin_top';
                        }

                        $border_bottom = $not_last_row ? 'border-bottom: 1px solid #d9d9d9;' : 'border-bottom: 1px solid black;';

                        for ($col = 0; $col < 8; $col++) {
                            $vehicle_name = '';
                            $vehicle_name_chinese = '';
                            $vehicle_editted = '';
                            $vehicle_double = '';

                            $cur_count = $col + ($row * 8);

                            $last_col_border_right = $col == 7 ? " border-right:1px solid black; " : "";

                            if ($cur_count < $team_vehicle_list_count) {
                                if ($team_vehicle_list[$cur_count] != null) {
                                    $vehicle_name = $team_vehicle_list[$col + ($row * 8)]->car_plate;

                                    if (count($job_sheet->job_sheet_histories->where('history_type', 1)->where('ref_id_1', $team_vehicle_list[$col + ($row * 8)]->id)->where('ref_id_2', $team->id)) > 0 || $is_new_team) {
                                        $vehicle_editted = 'editted';
                                    }

                                    $vehicle_double = in_array($team_vehicle_list[$cur_count]->id, $vehicle_id_double) ? 'double' : '';

                                    $vehicle_name_chinese = $thisController->containChineseCharacters($vehicle_name);
                                }
                            }

                            $team_vehicle_list_string .= "<td class='{$vehicle_editted} table_detail_value {$thin_top}' style='{$last_col_border_right} {$border_bottom}'><span class='{$vehicle_double} {$vehicle_name_chinese}'>{$vehicle_name}</span></td>";
                        }

                        if ($row != ($row_count - 1)) {
                            $team_vehicle_list_string .= "</tr>";
                        }
                    }
                } else {
                    $team_vehicle_list_string .= "<td colspan='2' class='table_detail_key' style='border: 1px solid black;'>Vehicle :</td>
                    <td class='table_detail_value' style='border-bottom: 1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom: 1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom: 1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom: 1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom: 1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom: 1px solid black;'></td>
                    <td class='table_detail_value' style='border-bottom: 1px solid black;'></td>
                    <td class='table_detail_value' style='border-right: 1px solid black;border-bottom: 1px solid black;'></td>";
                }

                $team_vehicle_list_string .= "</tr>";

                // Team Task
                $team_task_list_string = "";
                $team_task_list = $team->team_tasks;

                if ($team->team_tasks != null && count($team_task_list) > 0) {
                    $team_task_list_string .= "";

                    $current_task_count = 0;

                    foreach ($team->team_tasks as $task) {
                        $is_new_task = count($job_sheet->job_sheet_histories->where('history_type', 10)->where('ref_id_1', $task->id)) > 0;

                        $current_task_count_string = $current_task_count + 1;

                        $name_editted = count($job_sheet->job_sheet_histories->where('history_type', 4)->where('ref_id_1', $task->id)) > 0 || $is_new_team || $is_new_task ? 'editted' : '';

                        $team_task_list_string .= "<tr><td class='{$name_editted}' style='width: 10%; font-weight: bold;border-style:solid;border-color:black black white black;border-width:1px 1px 0 1px;'>Task {$current_task_count_string}</td>";

                        $task_name = $task->name;
                        $booth_count_string = ' - ';
                        $brands_string = $task->brand_name;
                        $brand_editted = '';
                        $brand_chinese = '';
                        $task_name_chinese = '';

                        if ($task->task_id != null || $task->task_id > 0) {
                            $this_task = Task::find($task->task_id);

                            if ($this_task != null) {
                                $task_name = $this_task->name;
                            }
                        }

                        if (count($job_sheet->job_sheet_histories->where('history_type', 8)->where('ref_id_1', $task->id)) > 0 || $is_new_team || $is_new_task) {
                            $brand_editted = 'editted';
                        }

                        if (isset($task->no_of_booth) && $task->no_of_booth > 0) {
                            $booth_count_string = " (" . $task->no_of_booth . ") - ";
                        }

                        if (isset($task->brands) && count($task->brands) > 0) {
                            if (count($task->brands) > 5) {
                                $brands_string = "Multiple brands";
                            } else {
                                $brands_string = ($task->brands->pluck('name'))->implode(', ');
                            }
                        }

                        $brand_chinese = $thisController->containChineseCharacters($brands_string);

                        $task_name_chinese = $thisController->containChineseCharacters($task_name);

                        $team_task_list_string .= "<td style='width: 10%; padding-top: 2px; padding-bottom: 2px; padding-right: 2px;border-bottom:1px solid #d9d9d9;'><span style='float: right;'>Branding :</span></td><td colspan='8' class='{$brand_editted} {$name_editted}' style='width: 80%; padding-top: 2px; padding-bottom: 2px; padding-left: 5px; padding-right: 5px; border-style:solid;border-width:1px;border-color: black black #d9d9d9 black;'><span class='{$task_name_chinese}'>{$task_name}</span>{$booth_count_string}<span class='{$brand_chinese}'>{$brands_string}</span></td></tr>";

                        $task_location_string = '-';
                        $location_editted = '';
                        $location_chinese = '';

                        if (isset($task->location_name) && !empty($task->location_name)) {
                            $task_location_string = $task->location_name;
                        }

                        if ($task->location_id != null && $task->location_id > 0) {
                            $this_location = Location::find($task->location_id);

                            if ($this_location != null) {
                                $task_location_string = $this_location->name;
                            }
                        }

                        if (count($job_sheet->job_sheet_histories->where('history_type', 6)->where('ref_id_1', $task->id)) > 0 || $is_new_team || $is_new_task) {
                            $location_editted = 'editted';
                        }

                        $location_chinese = $thisController->containChineseCharacters($task_location_string);

                        $team_task_list_string .= "<tr><td style='width: 10%;border-style:solid;border-color:black;border-width:0 1px 1px 1px;'></td><td style='width: 10%; padding-top: 2px; padding-bottom: 2px; padding-left: 5px; padding-right: 2px;border-top:1px solid #d9d9d9;border-bottom:1px solid black;'><span style='float: right;'>Venue :</span></td><td colspan='8' class='{$location_editted} {$location_chinese}' style='width: 80%; padding-top: 2px; padding-bottom: 2px; padding-left: 5px; padding-right: 5px;border-style:solid;border-width:1px;border-color:#d9d9d9 black black black;'>{$task_location_string}</td></tr>";

                        $current_task_count++;
                    }
                }

                $team_name_editted = count($job_sheet->job_sheet_histories->where('history_type', 2)->where('ref_id_1', $team->id)) > 0 || $is_new_team ? 'editted' : '';
                $team_time_editted = count($job_sheet->job_sheet_histories->where('history_type', 5)->where('ref_id_1', $team->id)) > 0 || $is_new_team ? 'editted' : '';
                $team_overnight_editted = count($job_sheet->job_sheet_histories->where('history_type', 3)->where('ref_id_1', $team->id)) || $is_new_team > 0 ? 'editted' : '';

                $team_time_string = isset($team['time']) ? (new DateTime($team['time']))->format('h:i A') : "-";

                $team_name_chinese = $thisController->containChineseCharacters($team['name']);

                $team_table_string = "<table style='width: 100%;'>
                <tr style='background-color: #ddebf7; text-align: center;'>
                    <th colspan='2' style='padding-left: 7px; padding-right: 7px; width: 20%;border:1px solid black;' class='{$team_name_editted} {$team_name_chinese}'>Team <span>{$team['name']}</span></th>
                    <th colspan='3' style='padding-left: 7px; padding-right: 7px; width: 30%;border:1px solid black;' class='{$team_time_editted}'>Work time : {$team_time_string}</th>
                    <th colspan='3' style='padding-left: 7px; padding-right: 7px; width: 30%;border:1px solid black;' class='{$team_overnight_editted}'>Overnight : {$team['overnight']}</th>
                    <th colspan='2' style='padding-left: 7px; padding-right: 7px; width: 20%;border:1px solid black;'>Total : {$staff_count}</th>
                </tr>
                {$team_task_list_string}
                {$leader_list_string}
                {$team_member_list_string}
                {$team_vehicle_list_string}
            </table>
            <br>";

                $team_table_list_string .= $team_table_string;
            }
        }

        // Annual Leave
        if (count($latest_version_team) == 0 || in_array('AL', $latest_version_team)) {
            $annual_leave_list_string = "<table style='width: 100%; background-color: #ddebf7; border:1px solid black;'><tr>";

            $annual_leave_list = $job_sheet->annual_leaves;
            $annual_leave_list_count = count($job_sheet->annual_leaves);

            if ($annual_leave_list_count > 0) {
                $annual_leave_list = $annual_leave_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($annual_leave_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $annual_leave_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom} border-right:1px solid black;'><span style=''>Annual Leave : </span><span style='float: right;'>{$annual_leave_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $annual_leave_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last} border-right:1px solid black;'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $annual_leave_list_count) {
                            if ($annual_leave_list[$cur_count] != null) {
                                $member_name = $annual_leave_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $annual_leave_list[$cur_count]->id)->where('ref_id_2', 'AL')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $annual_leave_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $annual_leave_list_string .= "</tr>";
                    }
                }
            } else {
                $annual_leave_list_string .= "<td class='table_detail_key' style='border-right:1px solid black;'>Annual Leave :</td>
                <td class='table_detail_value' ></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $annual_leave_list_string .= "</tr></table><br>";
        } else {
            $annual_leave_list_string = "";
        }

        // MC
        if (count($latest_version_team) == 0 || in_array('MC', $latest_version_team)) {
            $mc_list_string = "<table style='width: 100%; background-color: #ddebf7;border:1px solid black;'><tr>";

            $mc_list = $job_sheet->medical_leaves;
            $mc_list_count = count($job_sheet->medical_leaves);

            if ($mc_list_count > 0) {
                $mc_list = $mc_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($mc_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $mc_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom} border-right:1px solid black;'><span style=''>Medical Leave : </span> <span style='float: right;'>{$mc_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $mc_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last} border-right:1px solid black;'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $mc_list_count) {
                            if ($mc_list[$cur_count] != null) {
                                $member_name = $mc_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $mc_list[$cur_count]->id)->where('ref_id_2', 'MC')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $mc_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $mc_list_string .= "</tr>";
                    }
                }
            } else {
                $mc_list_string .= "<td class='table_detail_key' style='border-right:1px solid black;'>Medical Leave :</td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $mc_list_string .= "</tr></table><br>";
        } else {
            $mc_list_string = "";
        }

        // Emergency Leave
        if (count($latest_version_team) == 0 || in_array('EL', $latest_version_team)) {
            $emergency_leave_list_string = "<table style='width: 100%; background-color: #ddebf7;border:1px solid black;'><tr>";

            $emergency_leave_list = $job_sheet->emergency_leaves;
            $emergency_leave_list_count = count($job_sheet->emergency_leaves);

            if ($emergency_leave_list_count > 0) {
                $emergency_leave_list = $emergency_leave_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($emergency_leave_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $emergency_leave_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom} border-right:1px solid black;'><span style=''>Emergency Leave : </span><span style='float: right;'>{$emergency_leave_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $emergency_leave_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last} border-right:1px solid black;'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $emergency_leave_list_count) {
                            if ($emergency_leave_list[$cur_count] != null) {
                                $member_name = $emergency_leave_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $emergency_leave_list[$cur_count]->id)->where('ref_id_2', 'EL')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $emergency_leave_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $emergency_leave_list_string .= "</tr>";
                    }
                }
            } else {
                $emergency_leave_list_string .= "<td class='table_detail_key' style='border-right:1px solid black;'>Emergency Leave :</td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $emergency_leave_list_string .= "</tr></table><br>";
        } else {
            $emergency_leave_list_string = "";
        }

        // Holiday
        if (count($latest_version_team) == 0 || in_array('HOL', $latest_version_team)) {
            $holiday_list_string = "<table style='width: 100%; background-color: #ddebf7;border:1px solid black;'><tr>";

            $holiday_list = $job_sheet->holidays;
            $holiday_list_count = count($job_sheet->holidays);

            if ($holiday_list_count > 0) {
                $holiday_list = $holiday_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($holiday_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $holiday_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom} border-right:1px solid black;'><span style=''>Holiday : </span> <span style='float: right;'>{$holiday_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $holiday_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last} border-right:1px solid black;'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $holiday_list_count) {
                            if ($holiday_list[$cur_count] != null) {
                                $member_name = $holiday_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $holiday_list[$cur_count]->id)->where('ref_id_2', 'HOL')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $holiday_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $holiday_list_string .= "</tr>";
                    }
                }
            } else {
                $holiday_list_string .= "<td class='table_detail_key' style='border-right:1px solid black;'>Holiday :</td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $holiday_list_string .= "</tr></table>";
        } else {
            $holiday_list_string = "";
        }

        $leave_table_string = $annual_leave_list_string . $mc_list_string . $emergency_leave_list_string . $holiday_list_string;

        $staff_id_list = array_merge($staff_id_list, $job_sheet->annual_leaves->pluck('id')->toArray());
        $staff_id_list = array_merge($staff_id_list, $job_sheet->medical_leaves->pluck('id')->toArray());
        $staff_id_list = array_merge($staff_id_list, $job_sheet->emergency_leaves->pluck('id')->toArray());
        $staff_id_list = array_merge($staff_id_list, $job_sheet->holidays->pluck('id')->toArray());


        /* $html = view('job_sheet_template', [
            'job_sheet_date' => (new DateTime($job_sheet['job_sheet_date']))->format('j-n-y (l)'),
            'draft' => $is_draft ? '- DRAFT' : '',
            'last_updated_at' => (new DateTime($job_sheet['updated_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d/m/Y h:i A'),
            'updated_by' => isset($job_sheet['update_by_user']) ? $job_sheet['update_by_user']->name : '-',
            'updated_by_chinese' => isset($job_sheet['update_by_user']) ? $thisController->containChineseCharacters($job_sheet['update_by_user']->name) : '',
            'team_table_list_string' => $team_table_list_string,
            'leave_table_string' => $leave_table_string,
            'total_staff' => Staff::count(),
            'total_assigned_staff' => count(array_unique($staff_id_list)),
            'total_repeat' => count($staff_id_double),
            'total_vehicle' => Vehicle::where('rented', false)->count(),
            'total_asisgned_vehicle' => Vehicle::where('rented', false)->whereIn('id', $vehicle_id_list)->count(),
            'total_rental' => Vehicle::whereIn('id', $vehicle_id_list)->where('rented', true)->count(),
        ])->render();

        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('cid0cs', '', 12);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('jobsheet_' . ((new DateTime($job_sheet->job_sheet_date))->format('Ymd')) . '.pdf', 'D'); */


        /* $pdf = Pdf::loadView('job_sheet_template', [
            'job_sheet_date' => (new DateTime($job_sheet['job_sheet_date']))->format('j-n-y (l)'),
            'draft' => $is_draft ? '- DRAFT' : '',
            'last_updated_at' => (new DateTime($job_sheet['updated_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d/m/Y h:i A'),
            'updated_by' => isset($job_sheet['update_by_user']) ? $job_sheet['update_by_user']->name : '-',
            'updated_by_chinese' => isset($job_sheet['update_by_user']) ? $thisController->containChineseCharacters($job_sheet['update_by_user']->name) : '',
            'team_table_list_string' => $team_table_list_string,
            'leave_table_string' => $leave_table_string,
            'total_staff' => Staff::count(),
            'total_assigned_staff' => count(array_unique($staff_id_list)),
            'total_repeat' => count($staff_id_double),
            'total_vehicle' => Vehicle::where('rented', false)->count(),
            'total_asisgned_vehicle' => Vehicle::where('rented', false)->whereIn('id', $vehicle_id_list)->count(),
            'total_rental' => Vehicle::whereIn('id', $vehicle_id_list)->where('rented', true)->count(),
        ])->setPaper('A4');

        $options = $pdf->getOptions();
        $options->setFontCache(storage_path('fonts'));
        $options->set('isRemoteEnabled', true);
        $options->set('pdfBackend', 'CPDF');
        $options->set('enable_font_subsetting', true);
        $options->setChroot([
            'resources/views/',
            storage_path('fonts'),
        ]);


        return $pdf->download('jobsheet_' . ((new DateTime($job_sheet->job_sheet_date))->format('Ymd')) . '.pdf'); */
        $data = [
            'job_sheet_date' => (new DateTime($job_sheet['job_sheet_date']))->format('j-n-y (l)'),
            'draft' => $is_draft ? '- DRAFT' : '',
            'last_updated_at' => (new DateTime($job_sheet['updated_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d/m/Y h:i A'),
            'updated_by' => isset($job_sheet['update_by_user']) ? $job_sheet['update_by_user']->name : '-',
            'updated_by_chinese' => isset($job_sheet['update_by_user']) ? $thisController->containChineseCharacters($job_sheet['update_by_user']->name) : '',
            'team_table_list_string' => $team_table_list_string,
            'leave_table_string' => $leave_table_string,
            'total_staff' => Staff::count(),
            'total_assigned_staff' => count(array_unique($staff_id_list)),
            'total_repeat' => count($staff_id_double),
            'total_vehicle' => Vehicle::where('rented', false)->count(),
            'total_asisgned_vehicle' => Vehicle::where('rented', false)->whereIn('id', $vehicle_id_list)->count(),
            'total_rental' => Vehicle::whereIn('id', $vehicle_id_list)->where('rented', true)->count(),
        ];

        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('job_sheet_template', $data, [], [
            'mode' => '-aCJK',
            'custom_font_dir' => storage_path('fonts'),
            'custom_font_data' => [
                'yahei' => [
                    'R' => 'yahei.ttf',    // regular font
                    'B' => 'yahei.ttf',       // optional: bold font
                ],
                'arial' => [
                    'R' => 'Arial.ttf',    // regular font
                    'B' => 'Arial_Bold.ttf',       // optional: bold font
                ]
            ]
        ]);

        return $pdf->download('jobsheet_' . ((new DateTime($job_sheet->job_sheet_date))->format('Ymd')) . '.pdf');
    }

    public static function previewPDF(Request $request)
    {
        $thisController = new JobSheetController();

        $job_sheet_id = is_numeric($request->input('j')) ? $request->input('j') : null;
        $only_latest = ($request->input('l') ?? 0) == 1;

        if (!isset($job_sheet_id)) {
            return response()->json([
                'message' => 'Invalid Job Sheet ID',
            ], 400);
        }

        $job_sheet = JobSheet::with(['job_sheet_histories', 'annual_leaves.grouping', 'medical_leaves.grouping', 'emergency_leaves.grouping', 'update_by_user'])->firstWhere('id', $job_sheet_id);

        if (!isset($job_sheet)) {
            return response()->json([
                'message' => 'Job Sheet not found',
            ], 404);
        }

        $latest_version_team = [];

        if (count($job_sheet->job_sheet_histories) == 0) {
            $only_latest = false;
        }

        if ($only_latest) {
            $latest_version = $job_sheet->job_sheet_histories->max('version');

            $latest_version_team = $job_sheet->job_sheet_histories->where('version', $latest_version)->pluck('ref_id_2')->toArray();
        }

        $is_draft = $job_sheet->status_flag == 0;

        $teams = Team::where('job_sheet_id', $job_sheet_id)
            ->with(['leaders.grouping', 'team_members.grouping', 'team_vehicles.vehicle_type', 'team_tasks'])
            ->orderBy('team_no')
            ->get();

        $team_table_list_string = "";

        $staff_id_list = [];
        $vehicle_id_list = [];

        foreach ($teams as $team) {
            $staff_id_list = array_merge($staff_id_list, $team->leaders->pluck('id')->toArray());
            $staff_id_list = array_merge($staff_id_list, $team->team_members->pluck('id')->toArray());
            $vehicle_id_list = array_merge($vehicle_id_list, $team->team_vehicles->pluck('id')->toArray());
        }

        $staff_id_double = array_keys(array_filter(array_count_values($staff_id_list), function ($count) {
            return $count > 1;
        }));

        $vehicle_id_double = array_keys(array_filter(array_count_values($vehicle_id_list), function ($count) {
            return $count > 1;
        }));

        foreach ($teams as $team) {
            if (count($latest_version_team) == 0 || in_array($team->id, $latest_version_team)) {
                $staff_count = 0;

                // Leader 
                $leader_list_string = "<tr>";

                $leader_list = $team->leaders;
                $leader_list_count = count($leader_list);

                if ($leader_list_count > 0) {
                    $staff_count += $leader_list_count;

                    $leader_list = $leader_list->sortBy([
                        fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                        fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                    ])->values()->all();

                    $row_count = ceil($leader_list_count / 8);

                    $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid white;" : "";

                    $leader_list_string .= "<td colspan='2' class='table_detail_key thin_bottom' style='{$multirow_border_bottom}'><span style=''>Leader :</span><span style='float: right;'>{$leader_list_count}</span></td>";

                    for ($row = 0; $row < $row_count; $row++) {
                        $not_last_row = $row != ($row_count - 1);

                        $thin_top = '';

                        if ($row > 0) {
                            $mutirow_not_last = $not_last_row ? "border-bottom:1px solid white;" : "";

                            $leader_list_string .= "<tr><td colspan='2' class='table_detail_key thin_bottom' style='border-top:1px solid white;{$mutirow_not_last}'></td>";

                            $thin_top = 'thin_top';
                        }

                        for ($col = 0; $col < 8; $col++) {
                            $leader_name = '';
                            $leader_name_chinese = '';
                            $leader_editted = '';
                            $leader_double = '';
                            $can_drive_lorry = '';

                            $cur_count = $col + ($row * 8);

                            if ($cur_count < $leader_list_count) {
                                if ($leader_list[$cur_count] != null) {
                                    $this_leader = $leader_list[$cur_count];

                                    $leader_name = $this_leader->name;

                                    if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $this_leader->id)->where('ref_id_2', $team->id)) > 0) {
                                        $leader_editted = 'editted';
                                    }

                                    $leader_double = in_array($this_leader->id, $staff_id_double) ? 'double' : '';

                                    $can_drive_lorry = $this_leader->can_drive_lorry ? 'can_drive_lorry' : '';

                                    $leader_name_chinese = $thisController->containChineseCharacters($leader_name);
                                }
                            }

                            $leader_list_string .= "<td class='{$leader_editted} table_detail_value {$thin_top} thin_bottom'><span class='{$leader_double} {$can_drive_lorry} {$leader_name_chinese}'>{$leader_name}</span></td>";
                        }

                        if ($not_last_row) {
                            $leader_list_string .= "</tr>";
                        }
                    }
                } else {
                    $leader_list_string .= "<td colspan='2' class='table_detail_key thin_bottom'>Leader :</td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>
                    <td class='table_detail_value thin_bottom'></td>";
                }

                $leader_list_string .= "</tr>";

                // Team Member
                $team_member_list_string = "<tr>";

                $team_member_list = $team->team_members;
                $team_member_list_count = count($team_member_list);

                if ($team_member_list_count > 0) {
                    $staff_count += $team_member_list_count;

                    $team_member_list = $team_member_list->sortBy([
                        fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                        fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                    ])->values()->all();

                    $row_count = ceil($team_member_list_count / 8);

                    $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid white;" : "";

                    $team_member_list_string .= "<td colspan='2' class='table_detail_key thin_top' style='{$multirow_border_bottom}'><span style=''>Member :</span><span style='float: right;'>{$team_member_list_count}</span></td>";

                    for ($row = 0; $row < $row_count; $row++) {
                        $not_last_row = $row != ($row_count - 1);

                        if ($row > 0) {
                            $mutirow_not_last = $not_last_row ? "border-bottom:1px solid white;" : "";

                            $team_member_list_string .= "<tr><td colspan='2' class='table_detail_key' style='border-top:1px solid white;{$mutirow_not_last}'></td>";
                        }

                        $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                        for ($col = 0; $col < 8; $col++) {
                            $member_name = '';
                            $member_name_chinese = '';
                            $member_editted = '';
                            $member_double = '';
                            $can_drive_lorry = '';

                            $cur_count = $col + ($row * 8);

                            if ($cur_count < $team_member_list_count) {
                                if ($team_member_list[$cur_count] != null) {
                                    $this_member = $team_member_list[$cur_count];

                                    $member_name = $this_member->name;

                                    if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $this_member->id)->where('ref_id_2', $team->id)) > 0) {
                                        $member_editted = 'editted';
                                    }

                                    $member_double = in_array($this_member->id, $staff_id_double) ? 'double' : '';

                                    $can_drive_lorry = $this_member->can_drive_lorry ? 'can_drive_lorry' : '';

                                    $member_name_chinese = $thisController->containChineseCharacters($member_name);
                                }
                            }

                            $team_member_list_string .= "<td class='{$member_editted} table_detail_value thin_top {$thin_bottom}'><span class='{$member_double} {$can_drive_lorry} {$member_name_chinese}'>{$member_name}</span></td>";
                        }

                        if ($row != ($row_count - 1)) {
                            $team_member_list_string .= "</tr>";
                        }
                    }
                } else {
                    $team_member_list_string .= "<td colspan='2' class='table_detail_key'>Member :</td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>";
                }

                $team_member_list_string .= "</tr>";

                // Team Vehicle
                $team_vehicle_list_string = "<tr>";

                $team_vehicle_list = $team->team_vehicles;
                $team_vehicle_list_count = count($team->team_vehicles);

                if ($team_vehicle_list_count > 0) {
                    $row_count = ceil($team_vehicle_list_count / 8);

                    $team_vehicle_list = $team_vehicle_list->sortBy([
                        fn($a, $b) => $a['vehicle_type']['seq_no'] <=> $b['vehicle_type']['seq_no'],
                        fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                    ])->values()->all();

                    $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid white;" : "";

                    $team_vehicle_list_string .= "<td colspan='2' class='table_detail_key' style='{$multirow_border_bottom}'><span style=''>Vehicle :</span><span style='float: right;'>{$team_vehicle_list_count}</span></td>";

                    for ($row = 0; $row < $row_count; $row++) {
                        $not_last_row = $row != ($row_count - 1);

                        $thin_top = '';

                        if ($row > 0) {
                            $mutirow_not_last = $not_last_row ? "border-bottom:1px solid white;" : "";

                            $team_vehicle_list_string .= "<tr><td colspan='2' class='table_detail_key' style='border-top:1px solid white;{$mutirow_not_last}'></td>";

                            $thin_top = 'thin_top';
                        }

                        $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                        for ($col = 0; $col < 8; $col++) {
                            $vehicle_name = '';
                            $vehicle_name_chinese = '';
                            $vehicle_editted = '';
                            $vehicle_double = '';

                            $cur_count = $col + ($row * 8);

                            if ($cur_count < $team_vehicle_list_count) {
                                if ($team_vehicle_list[$cur_count] != null) {
                                    $vehicle_name = $team_vehicle_list[$col + ($row * 8)]->car_plate;

                                    if (count($job_sheet->job_sheet_histories->where('history_type', 1)->where('ref_id_1', $team_vehicle_list[$col + ($row * 8)]->id)->where('ref_id_2', $team->id)) > 0) {
                                        $vehicle_editted = 'editted';
                                    }

                                    $vehicle_double = in_array($team_vehicle_list[$cur_count]->id, $vehicle_id_double) ? 'double' : '';

                                    $vehicle_name_chinese = $thisController->containChineseCharacters($vehicle_name);
                                }
                            }

                            $team_vehicle_list_string .= "<td class='{$vehicle_editted} table_detail_value {$thin_top} {$thin_bottom}'><span class='{$vehicle_double} {$vehicle_name_chinese}'>{$vehicle_name}</span></td>";
                        }

                        if ($row != ($row_count - 1)) {
                            $team_vehicle_list_string .= "</tr>";
                        }
                    }
                } else {
                    $team_vehicle_list_string .= "<td colspan='2' class='table_detail_key'>Vehicle :</td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>
                    <td class='table_detail_value'></td>";
                }

                $team_vehicle_list_string .= "</tr>";

                // Team Task
                $team_task_list_string = "";
                $team_task_list = $team->team_tasks;

                if ($team->team_tasks != null && count($team_task_list) > 0) {
                    $team_task_list_string .= "";

                    $current_task_count = 0;

                    foreach ($team->team_tasks as $task) {
                        $current_task_count_string = $current_task_count + 1;

                        $name_editted = count($job_sheet->job_sheet_histories->where('history_type', 4)->where('ref_id_1', $task->id)) > 0 ? 'editted' : '';

                        $team_task_list_string .= "<tr><td class='{$name_editted}' style='width: 10%; font-weight: bold;border-bottom:1px solid white;'>Task {$current_task_count_string}</td>";

                        $task_name = $task->name;
                        $booth_count_string = ' - ';
                        $brands_string = $task->brand_name;
                        $brand_editted = '';
                        $brand_chinese = '';
                        $task_name_chinese = '';

                        if ($task->task_id != null || $task->task_id > 0) {
                            $this_task = Task::find($task->task_id);

                            if ($this_task != null) {
                                $task_name = $this_task->name;
                            }
                        }

                        if (count($job_sheet->job_sheet_histories->where('history_type', 8)->where('ref_id_1', $task->id)) > 0) {
                            $brand_editted = 'editted';
                        }

                        if (isset($task->no_of_booth) && $task->no_of_booth > 0) {
                            $booth_count_string = " (" . $task->no_of_booth . ") - ";
                        }

                        if (isset($task->brands) && count($task->brands) > 0) {
                            if (count($task->brands) > 5) {
                                $brands_string = "Multiple brands";
                            } else {
                                $brands_string = ($task->brands->pluck('name'))->implode(', ');
                            }
                        }

                        $brand_chinese = $thisController->containChineseCharacters($brands_string);

                        $task_name_chinese = $thisController->containChineseCharacters($task_name);

                        $team_task_list_string .= "<td style='width: 10%; padding-top: 2px; padding-bottom: 2px; padding-right: 2px;border-bottom:1px solid #d9d9d9;'><span style='float: right;'>Branding :</span></td><td colspan='8' class='{$brand_editted}' style='width: 80%; padding-top: 2px; padding-bottom: 2px; padding-left: 5px; padding-right: 5px; border-bottom:1px solid #d9d9d9;'><span class='{$task_name_chinese}'>{$task_name}</span>{$booth_count_string}<span class='{$brand_chinese}'>{$brands_string}</span></td></tr>";

                        $task_location_string = '-';
                        $location_editted = '';
                        $location_chinese = '';

                        if (isset($task->location_name) && !empty($task->location_name)) {
                            $task_location_string = $task->location_name;
                        }

                        if ($task->location_id != null && $task->location_id > 0) {
                            $this_location = Location::find($task->location_id);

                            if ($this_location != null) {
                                $task_location_string = $this_location->name;
                            }
                        }

                        if (count($job_sheet->job_sheet_histories->where('history_type', 6)->where('ref_id_1', $task->id)) > 0) {
                            $location_editted = 'editted';
                        }

                        $location_chinese = $thisController->containChineseCharacters($task_location_string);

                        $team_task_list_string .= "<tr><td style='width: 10%;border-top:1px solid white;'></td><td style='width: 10%; padding-top: 2px; padding-bottom: 2px; padding-left: 5px; padding-right: 2px;border-top:1px solid #d9d9d9;'><span style='float: right;'>Venue :</span></td><td colspan='8' class='{$location_editted} {$location_chinese}' style='width: 80%; padding-top: 2px; padding-bottom: 2px; padding-left: 5px; padding-right: 5px;border-top:1px solid #d9d9d9;'>{$task_location_string}</td></tr>";

                        $current_task_count++;
                    }
                }

                $team_name_editted = count($job_sheet->job_sheet_histories->where('history_type', 2)->where('ref_id_1', $team->id)) > 0 ? 'editted' : '';
                $team_time_editted = count($job_sheet->job_sheet_histories->where('history_type', 5)->where('ref_id_1', $team->id)) > 0 ? 'editted' : '';
                $team_overnight_editted = count($job_sheet->job_sheet_histories->where('history_type', 3)->where('ref_id_1', $team->id)) > 0 ? 'editted' : '';

                $team_time_string = isset($team['time']) ? (new DateTime($team['time']))->format('h:i A') : "-";

                $team_name_chinese = $thisController->containChineseCharacters($team['name']);

                $team_table_string = "<table style='width: 100%;'>
                <tr style='background-color: #ddebf7; text-align: center;'>
                    <th colspan='2' style='padding-left: 7px; padding-right: 7px; width: 20%;' class='{$team_name_editted} {$team_name_chinese}'>Team <span>{$team['name']}</span></th>
                    <th colspan='3' style='padding-left: 7px; padding-right: 7px; width: 30%;' class='{$team_time_editted}'>Work time : {$team_time_string}</th>
                    <th colspan='3' style='padding-left: 7px; padding-right: 7px; width: 30%;' class='{$team_overnight_editted}'>Overnight : {$team['overnight']}</th>
                    <th colspan='2' style='padding-left: 7px; padding-right: 7px; width: 20%;'>Total : {$staff_count}</th>
                </tr>
                {$team_task_list_string}
                {$leader_list_string}
                {$team_member_list_string}
                {$team_vehicle_list_string}
            </table>
            <br>";

                $team_table_list_string .= $team_table_string;
            }
        }

        // Annual Leave
        if (count($latest_version_team) == 0 || in_array('AL', $latest_version_team)) {
            $annual_leave_list_string = "<table style='width: 100%; background-color: #ddebf7;'><tr>";

            $annual_leave_list = $job_sheet->annual_leaves;
            $annual_leave_list_count = count($job_sheet->annual_leaves);

            if ($annual_leave_list_count > 0) {
                $annual_leave_list = $annual_leave_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($annual_leave_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $annual_leave_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom}'><span style=''>Annual Leave :</span><span style='float: right;'>{$annual_leave_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $annual_leave_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last}'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $annual_leave_list_count) {
                            if ($annual_leave_list[$cur_count] != null) {
                                $member_name = $annual_leave_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $annual_leave_list[$cur_count]->id)->where('ref_id_2', 'AL')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $annual_leave_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $annual_leave_list_string .= "</tr>";
                    }
                }
            } else {
                $annual_leave_list_string .= "<td class='table_detail_key'>Annual Leave :</td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $annual_leave_list_string .= "</tr></table><br>";
        } else {
            $annual_leave_list_string = "";
        }

        // MC
        if (count($latest_version_team) == 0 || in_array('MC', $latest_version_team)) {
            $mc_list_string = "<table style='width: 100%; background-color: #ddebf7;'><tr>";

            $mc_list = $job_sheet->medical_leaves;
            $mc_list_count = count($job_sheet->medical_leaves);

            if ($mc_list_count > 0) {
                $mc_list = $mc_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($mc_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $mc_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom}'><span style=''>Medical Leave :</span> <span style='float: right;'>{$mc_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $mc_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last}'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $mc_list_count) {
                            if ($mc_list[$cur_count] != null) {
                                $member_name = $mc_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $mc_list[$cur_count]->id)->where('ref_id_2', 'MC')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $mc_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $mc_list_string .= "</tr>";
                    }
                }
            } else {
                $mc_list_string .= "<td class='table_detail_key'>Medical Leave :</td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $mc_list_string .= "</tr></table><br>";
        } else {
            $mc_list_string = "";
        }

        // Emergency Leave
        if (count($latest_version_team) == 0 || in_array('EL', $latest_version_team)) {
            $emergency_leave_list_string = "<table style='width: 100%; background-color: #ddebf7;'><tr>";

            $emergency_leave_list = $job_sheet->emergency_leaves;
            $emergency_leave_list_count = count($job_sheet->emergency_leaves);

            if ($emergency_leave_list_count > 0) {
                $emergency_leave_list = $emergency_leave_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($emergency_leave_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $emergency_leave_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom}'><span style=''>Emergency Leave :</span><span style='float: right;'>{$emergency_leave_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $emergency_leave_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last}'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $emergency_leave_list_count) {
                            if ($emergency_leave_list[$cur_count] != null) {
                                $member_name = $emergency_leave_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $emergency_leave_list[$cur_count]->id)->where('ref_id_2', 'EL')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $emergency_leave_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $emergency_leave_list_string .= "</tr>";
                    }
                }
            } else {
                $emergency_leave_list_string .= "<td class='table_detail_key'>Emergency Leave :</td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $emergency_leave_list_string .= "</tr></table><br>";
        } else {
            $emergency_leave_list_string = "";
        }

        // Holiday
        if (count($latest_version_team) == 0 || in_array('HOL', $latest_version_team)) {
            $holiday_list_string = "<table style='width: 100%; background-color: #ddebf7;'><tr>";

            $holiday_list = $job_sheet->holidays;
            $holiday_list_count = count($job_sheet->holidays);

            if ($holiday_list_count > 0) {
                $holiday_list = $holiday_list->sortBy([
                    fn($a, $b) => $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'],
                    fn($a, $b) => $a['seq_no'] <=> $b['seq_no']
                ])->values()->all();

                $row_count = ceil($holiday_list_count / 8);

                $multirow_border_bottom = $row_count > 1 ? "border-bottom:1px solid #ddebf7;" : "";

                $holiday_list_string .= "<td class='table_detail_key' style='{$multirow_border_bottom}'><span style=''>Holiday :</span> <span style='float: right;'>{$holiday_list_count}</span></td>";

                for ($row = 0; $row < $row_count; $row++) {
                    $not_last_row = $row != ($row_count - 1);

                    $thin_top = '';

                    if ($row > 0) {
                        $mutirow_not_last = $not_last_row ? "border-bottom:1px solid #ddebf7;" : "";

                        $holiday_list_string .= "<tr><td class='table_detail_key' style='border-top:1px solid #ddebf7;{$mutirow_not_last}'></td>";

                        $thin_top = 'thin_top';
                    }

                    $thin_bottom = $not_last_row ? 'thin_bottom' : '';

                    for ($col = 0; $col < 8; $col++) {
                        $member_name = '';
                        $member_editted = '';
                        $member_chinese = '';

                        $cur_count = $col + ($row * 8);

                        if ($cur_count < $holiday_list_count) {
                            if ($holiday_list[$cur_count] != null) {
                                $member_name = $holiday_list[$col + ($row * 8)]->name;

                                if (count($job_sheet->job_sheet_histories->where('history_type', 0)->where('ref_id_1', $holiday_list[$cur_count]->id)->where('ref_id_2', 'HOL')) > 0) {
                                    $member_editted = 'editted';
                                }

                                $member_chinese = $thisController->containChineseCharacters($member_name);
                            }
                        }

                        $holiday_list_string .= "<td class='{$member_editted} table_detail_value {$thin_top} {$thin_bottom} {$member_chinese}'>{$member_name}</td>";
                    }

                    if ($row != ($row_count - 1)) {
                        $holiday_list_string .= "</tr>";
                    }
                }
            } else {
                $holiday_list_string .= "<td class='table_detail_key'>Holiday :</td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>
                <td class='table_detail_value'></td>";
            }

            $holiday_list_string .= "</tr></table>";
        } else {
            $holiday_list_string = "";
        }

        $leave_table_string = $annual_leave_list_string . $mc_list_string . $emergency_leave_list_string . $holiday_list_string;

        $staff_id_list = array_merge($staff_id_list, $job_sheet->annual_leaves->pluck('id')->toArray());
        $staff_id_list = array_merge($staff_id_list, $job_sheet->medical_leaves->pluck('id')->toArray());
        $staff_id_list = array_merge($staff_id_list, $job_sheet->emergency_leaves->pluck('id')->toArray());
        $staff_id_list = array_merge($staff_id_list, $job_sheet->holidays->pluck('id')->toArray());

        return view('job_sheet_template', [
            'job_sheet_date' => (new DateTime($job_sheet['job_sheet_date']))->format('j-n-y (l)'),
            'draft' => $is_draft ? '- DRAFT' : '',
            'last_updated_at' => (new DateTime($job_sheet['updated_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d/m/Y h:i A'),
            'updated_by' => isset($job_sheet['update_by_user']) ? $job_sheet['update_by_user']->name : '-',
            'updated_by_chinese' => isset($job_sheet['update_by_user']) ? $thisController->containChineseCharacters($job_sheet['update_by_user']->name) : '',
            'team_table_list_string' => $team_table_list_string,
            'leave_table_string' => $leave_table_string,
            'total_staff' => Staff::count(),
            'total_assigned_staff' => count(array_unique($staff_id_list)),
            'total_repeat' => count($staff_id_double),
            'total_vehicle' => Vehicle::where('rented', false)->count(),
            'total_asisgned_vehicle' => Vehicle::where('rented', false)->whereIn('id', $vehicle_id_list)->count(),
            'total_rental' => Vehicle::whereIn('id', $vehicle_id_list)->where('rented', true)->count(),
        ]);
    }

    public static function containChineseCharacters($text)
    {
        $pattern = '/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{20000}-\x{2A6DF}\x{2A700}-\x{2B73F}\x{2B740}-\x{2B81F}\x{2B820}-\x{2CEAF}\x{2CEB0}-\x{2EBEF}\x{30000}-\x{3134F}\x{F900}-\x{FAFF}\x{2F800}-\x{2FA1F}\x{2F00}-\x{2FDF}\x{2E80}-\x{2EFF}\x{3000}-\x{303F}\x{FF01}-\x{FF60}\x{FE30}-\x{FE4F}]/u';
        return preg_match($pattern, $text) ? "chinese" : "";
    }

    public static function compareByGroupingAndSeqNo($a, $b)
    {
        // Compare by grouping.seq_no
        $groupingComparison = $a['grouping']['seq_no'] <=> $b['grouping']['seq_no'];

        // If grouping.seq_no is the same, compare by seq_no
        if ($groupingComparison === 0) {
            return $a['seq_no'] <=> $b['seq_no'];
        }

        return $groupingComparison;
    }
}
