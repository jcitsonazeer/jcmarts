<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = 'refunds';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'order_id',
        'customer_id',
        'gateway',
        'gateway_refund_id',
        'amount',
        'currency',
        'status',
        'reason',
        'requested_by_id',
        'requested_at',
        'approved_by_id',
        'approved_at',
        'processed_at',
        'failed_reason',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
