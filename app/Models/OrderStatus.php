<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $table = 'order_status';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_status',
        'action_time',
        'action_done_by_id',
    ];

    protected $casts = [
        'action_time' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
