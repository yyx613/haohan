<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkNature extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'work_natures';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at'
    ];

    public function staffs(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
