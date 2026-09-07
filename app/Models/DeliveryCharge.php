<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCharge extends Model
{
    protected $table = 'delivery_charges';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'delivery_charge',
        'free_delivery_above',
        'minimum_order_value',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'delivery_charge' => 'float',
        'free_delivery_above' => 'float',
        'minimum_order_value' => 'float',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];
}