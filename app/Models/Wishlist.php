<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $table = 'wishlist';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'product_id',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
