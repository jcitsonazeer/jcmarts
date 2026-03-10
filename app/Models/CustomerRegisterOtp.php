<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerRegisterOtp extends Model
{
    protected $table = 'customer_register_otp';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
