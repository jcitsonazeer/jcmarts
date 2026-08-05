<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $table = 'returns';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'customer_id',
        'reason',
        'customer_note',
        'status',
        'refund_amount',
        'sellable_stock',
        'admin_note',
        'requested_at',
        'approved_by_id',
        'approved_at',
        'rejected_at',
        'pickup_scheduled_at',
        'received_at',
        'inspected_at',
        'closed_at',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'sellable_stock' => 'boolean',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'received_at' => 'datetime',
        'inspected_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'return_id');
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
