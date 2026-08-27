<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class DeliveryPerson extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'delivery_persons';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'password',
        'vehicle_type',
        'vehicle_number',
        'status',
        'availability_status',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'delivery_person_id');
    }

    public function deliveryLocations(): HasMany
    {
        return $this->hasMany(DeliveryLocation::class, 'delivery_person_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }
}
