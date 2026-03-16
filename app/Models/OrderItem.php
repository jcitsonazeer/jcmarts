<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'rate_master_id',
        'quantity',
        'unit_price',
        'line_total',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function rate()
    {
        return $this->belongsTo(RateMaster::class, 'rate_master_id');
    }
}
