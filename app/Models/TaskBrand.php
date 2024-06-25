<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskBrand extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'task_brands';

    protected $fillable = [
        'name',
        'task_id',
        'created_at',
        'updated_at'
    ];

    public function team_tasks(): BelongsToMany
    {
        return $this->belongsToMany(TeamTask::class, 'team_task_brand', 'task_brand_id', 'team_task_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
