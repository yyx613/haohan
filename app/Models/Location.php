<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function team_tasks(): HasMany
    {
        return $this->hasMany(TeamTask::class);
    }
}
