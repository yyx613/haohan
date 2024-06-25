<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobSheet extends Model
{
    use HasFactory;

    protected $table = 'job_sheets';

    protected $fillable = [
        'job_sheet_date',
        'status_flag',
        'version',
        'created_at',
        'updated_at',
        'update_by'
    ];

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function annual_leaves()
    {
        return $this->belongsToMany(Staff::class, 'job_sheet_leaves', 'job_sheet_id', 'staff_id')->wherePivot('leave_type', 0)->withTrashed();
    }

    public function medical_leaves()
    {
        return $this->belongsToMany(Staff::class, 'job_sheet_leaves', 'job_sheet_id', 'staff_id')->wherePivot('leave_type', 1)->withTrashed();
    }

    public function emergency_leaves()
    {
        return $this->belongsToMany(Staff::class, 'job_sheet_leaves', 'job_sheet_id', 'staff_id')->wherePivot('leave_type', 2)->withTrashed();
    }

    public function holidays()
    {
        return $this->belongsToMany(Staff::class, 'job_sheet_leaves', 'job_sheet_id', 'staff_id')->wherePivot('leave_type', 3)->withTrashed();
    }

    public function update_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'update_by', 'id');
    }

    public function job_sheet_histories(): HasMany
    {
        return $this->hasMany(JobSheetHistory::class);
    }

    public static function boot()
    {
        parent::boot();

        self::deleting(function ($job_sheet) {
            $job_sheet->teams()->each(function ($team) {
                $team->delete();
            });

            JobSheetLeave::where('job_sheet_id', $job_sheet['id'])->delete();
            JobSheetHistory::where('job_sheet_id', $job_sheet['id'])->delete();
        });
    }
}
