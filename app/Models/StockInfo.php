<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockInfo extends Model
{
    protected $table = 'stock_info';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'rate_master_id',
        'stock_in_count',
        'sale_quantity',
        'current_stock',
        'sale_order_id',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'stock_in_count' => 'integer',
        'sale_quantity' => 'integer',
        'current_stock' => 'integer',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function rate()
    {
        return $this->belongsTo(RateMaster::class, 'rate_master_id');
    }
}
