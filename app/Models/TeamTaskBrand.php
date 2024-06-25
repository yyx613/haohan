<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamTaskBrand extends Model
{
    use HasFactory;

    protected $table = 'team_task_brand';

    protected $fillable = [
        'team_task_id',
        'brand_id',
        'created_at',
        'updated_at'
    ];
}
