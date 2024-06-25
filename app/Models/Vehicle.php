<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicles';

    protected $fillable = [
        'car_plate',
        'vehicle_type_id',
        'rented',
        'seq_no',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'rented' => 'boolean',
    ];

    public function vehicle_type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_vehicles', 'vehicle_id', 'team_id');
    }

    public function getCarPlateWithVehicleTypeAttribute()
    {
        return $this->vehicle_type->name . ' - ' . $this->car_plate;
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($vehicle) {
            if ($vehicle->seq_no == null || $vehicle->seq_no == 0) {
                $vehicle->seq_no = Vehicle::max('seq_no') + 1;
            }
        });
    }
}
