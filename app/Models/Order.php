<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'address_id',
        'sub_total',
        'delivery_charge',
        'packing_charge',
        'other_charge',
        'total_amount',
        'currency',
        'reservation_expires_at',
        'reservation_released_at',
        'reservation_release_reason',
        'delivered_at',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'sub_total' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'packing_charge' => 'decimal:2',
        'other_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'reservation_expires_at' => 'datetime',
        'reservation_released_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(OrderStatus::class, 'order_id')->orderBy('action_time')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'order_id');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'order_id');
    }
}
