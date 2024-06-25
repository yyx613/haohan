<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSheetHistory extends Model
{
    use HasFactory;

    protected $table = 'job_sheet_histories';

    protected $fillable = [
        'job_sheet_id',
        'history_type',
        'ref_id_1',
        'ref_id_2',
        'version',
        'update_by',
        'created_at',
        'updated_at'
    ];

    public function job_sheet(): BelongsTo
    {
        return $this->belongsTo(JobSheet::class);
    }
}
