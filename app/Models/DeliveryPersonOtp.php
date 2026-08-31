<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPersonOtp extends Model
{
    protected $table = 'delivery_person_otp';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'delivery_person_id',
        'otp_code',
        'otp_expires_at',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function deliveryPerson()
    {
        return $this->belongsTo(DeliveryPerson::class, 'delivery_person_id');
    }
}
