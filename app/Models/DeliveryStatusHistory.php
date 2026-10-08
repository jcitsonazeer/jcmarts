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

    public function getChangedByNameAttribute()
    {
        if ($this->changed_by_id === null || $this->changed_by_id === '') {
            return null;
        }

        // New rows store the actor type, so the name is never ambiguous.
        if ($this->changed_by_type === 'admin') {
            $admin = AdminLogin::find($this->changed_by_id);

            return $admin ? $admin->admin_username : ('Admin ' . $this->changed_by_id);
        }

        if ($this->changed_by_type === 'delivery_person') {
            $deliveryPerson = DeliveryPerson::find($this->changed_by_id);

            return $deliveryPerson ? $deliveryPerson->name : ('Delivery Person ' . $this->changed_by_id);
        }

        // Old rows saved before changed_by_type existed: fall back to the old lookup.
        $admin = AdminLogin::find($this->changed_by_id);

        if ($admin) {
            return $admin->admin_username;
        }

        $deliveryPerson = DeliveryPerson::find($this->changed_by_id);

        if ($deliveryPerson) {
            return $deliveryPerson->name;
        }

        return 'User ' . $this->changed_by_id;
    }
}
