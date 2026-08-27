<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryStatusHistory extends Model
{
    protected $table = 'delivery_status_histories';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'delivery_id',
        'old_status',
        'new_status',
        'changed_by_id',
        'changed_at',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
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
