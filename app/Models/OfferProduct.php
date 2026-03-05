<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferProduct extends Model
{
    protected $table = 'offer_products';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'offer_id',
        'products_id',
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

    public function offer()
    {
        return $this->belongsTo(OfferDetail::class, 'offer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'products_id');
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
