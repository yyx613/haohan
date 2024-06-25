<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSheetLeave extends Model
{
    use HasFactory;

    protected $table = 'job_sheet_leaves';

    protected $fillable = [
        'job_sheet_id',
        'staff_id',
        'leave_type',
        'created_at',
        'updated_at'
    ];

    public function job_sheet(): BelongsTo
    {
        return $this->belongsTo(JobSheet::class);
    }
}
