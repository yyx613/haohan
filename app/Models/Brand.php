<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function team_tasks(): BelongsToMany
    {
        return $this->belongsToMany(TeamTask::class, 'team_task_brand', 'brand_id', 'team_task_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'brand_task', 'brand_id', 'task_id');
    }
}
