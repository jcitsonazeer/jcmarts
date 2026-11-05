<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnStatusHistory extends Model
{
    protected $table = 'return_status_histories';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'return_id',
        'old_status',
        'new_status',
        'changed_by_id',
        'changed_by_type',
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

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, 'return_id');
    }

    public function getChangedByNameAttribute()
    {
        if ($this->changed_by_id === null || $this->changed_by_id === '') {
            return null;
        }

        if ($this->changed_by_type === 'admin') {
            $admin = AdminLogin::find($this->changed_by_id);

            return $admin ? $admin->admin_username : ('Admin ' . $this->changed_by_id);
        }

        if ($this->changed_by_type === 'delivery_person') {
            $deliveryPerson = DeliveryPerson::find($this->changed_by_id);

            return $deliveryPerson ? $deliveryPerson->name : ('Delivery Person ' . $this->changed_by_id);
        }

        if ($this->changed_by_type === 'customer') {
            $customer = Customer::find($this->changed_by_id);

            return $customer ? $customer->name : ('Customer ' . $this->changed_by_id);
        }

        return 'User ' . $this->changed_by_id;
    }
}
