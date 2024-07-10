<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamTask extends Model
{
    use HasFactory;

    protected $table = 'team_tasks';

    protected $fillable = [
        'id',
        'team_id',
        'task_no',
        'task_id',
        'name',
        'location_id',
        'location_name',
        'brand_name',
        'remark',
        'no_of_booth',
        'created_at',
        'updated_at'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'team_task_brand', 'team_task_id', 'brand_id');
    }

    public function getTaskNameAttribute()
    {
        return $this->task->name;
    }

    public function getLocationIdAttribute($value)
    {
        return $value ?? -1;
    }

    public function getTaskIdAttribute($value)
    {
        return $value ?? -1;
    }

    public static function boot()
    {
        parent::boot();

        self::deleting(function ($team_task) {
            TeamTaskBrand::where('team_task_id', $team_task['id'])->delete();
        });

        self::creating(function ($team_task) {
            if ($team_task->task_id == -1) {
                $team_task->task_id = null;
            }

            if ($team_task->location_id == -1) {
                $team_task->location_id = null;
            }
        });

        self::updating(function ($team_task) {
            if ($team_task->task_id == -1) {
                $team_task->task_id = null;
            }

            if ($team_task->location_id == -1) {
                $team_task->location_id = null;
            }
        });

        self::created(function ($task) {
            $team = Team::find($task['team_id']);

            if ($team != null) {
                $job_sheet = $team->job_sheet()->first();

                if ($job_sheet != null && $job_sheet['status_flag'] == 1) {

                    JobSheetHistory::firstOrCreate(
                        [
                            'job_sheet_id' => $job_sheet['id'],
                            'history_type' => 10,
                            'ref_id_1' => $task['id'],
                            'ref_id_2' => $task['team_id'],
                            'version' => $job_sheet['version']
                        ],
                        [
                            'update_by' => auth()->id()
                        ]
                    );
                }
            }
        });
    }
}
