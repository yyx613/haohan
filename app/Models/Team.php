<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';

    protected $fillable = [
        'job_sheet_id',
        'team_no',
        'name',
        'time',
        'overnight',
        'nightshift',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'nightshift' => 'boolean',
    ];

    public function job_sheet(): BelongsTo
    {
        return $this->belongsTo(JobSheet::class);
    }

    public function leaders()
    {
        return $this->belongsToMany(Staff::class, 'team_members', 'team_id', 'staff_id')->wherePivot('team_position', 1)->withTrashed();
    }

    public function team_members()
    {
        return $this->belongsToMany(Staff::class, 'team_members', 'team_id', 'staff_id')->wherePivot('team_position', 0)->withTrashed();
    }

    public function team_vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'team_vehicles', 'team_id', 'vehicle_id')->withTrashed();
    }

    public function team_tasks(): HasMany
    {
        return $this->hasMany(TeamTask::class);
    }

    public static function boot()
    {
        parent::boot();

        self::deleting(function ($team) {
            TeamMember::where('team_id', $team['id'])->delete();
            TeamVehicle::where('team_id', $team['id'])->delete();

            $team_task_list = TeamTask::where('team_id', $team['id'])->get();

            if (count($team_task_list) > 0) {
                $team_task_id_list = $team_task_list->pluck('id');

                TeamTaskBrand::whereIn('team_task_id', $team_task_id_list)->delete();
            }

            TeamTask::where('team_id', $team['id'])->delete();
        });
    }
}
