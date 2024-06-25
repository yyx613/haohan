<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grouping extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'groupings';

    protected $fillable = [
        'name',
        'seq_no',
        'created_at',
        'updated_at'
    ];

    public function staffs(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
