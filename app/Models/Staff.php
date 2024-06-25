<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staffs';

    protected $fillable = [
        'name',
        'role_id',
        'grouping_id',
        'work_nature_id',
        'seq_no',
        'can_drive_lorry',
        'status_flag',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'can_drive_lorry' => 'boolean',
    ];

    public function grouping(): BelongsTo
    {
        return $this->belongsTo(Grouping::class);
    }

    public function work_nature(): BelongsTo
    {
        return $this->belongsTo(WorkNature::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members', 'staff_id', 'team_id');
    }

    public function annual_leaves(): BelongsToMany
    {
        return $this->belongsToMany(JobSheet::class, 'job_sheet_leaves', 'staff_id', 'job_sheet_id')->wherePivot('leave_type', 0);
    }

    public function medical_leaves(): BelongsToMany
    {
        return $this->belongsToMany(JobSheet::class, 'job_sheet_leaves', 'staff_id', 'job_sheet_id')->wherePivot('leave_type', 1);
    }

    public function emergency_leaves(): BelongsToMany
    {
        return $this->belongsToMany(JobSheet::class, 'job_sheet_leaves', 'staff_id', 'job_sheet_id')->wherePivot('leave_type', 2);
    }

    public function holidays(): BelongsToMany
    {
        return $this->belongsToMany(JobSheet::class, 'job_sheet_leaves', 'staff_id', 'job_sheet_id')->wherePivot('leave_type', 3);
    }

    public function getNameWithRoleAttribute()
    {
        if ($this->role != null) {
            return $this->role->name . ' - ' . $this->name;
        }

        return $this->name;
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($staff) {
            if ($staff->seq_no == null || $staff->seq_no == 0) {
                $staff->seq_no = Staff::max('seq_no') + 1;
            }
        });
    }
}
