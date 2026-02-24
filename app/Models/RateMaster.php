<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateMaster extends Model
{
    protected $table = 'rate_master';

    protected $primaryKey = 'id';

    public $timestamps = false; // Using created_date / updated_date

    protected $fillable = [
        'product_id',
        'uom_id',
        'cost_price',
        'selling_price',
        'offer_percentage',
        'offer_price',
        'final_price',
        'stock_qty',
        'selected_display',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'cost_price'       => 'decimal:2',
        'selling_price'    => 'decimal:2',
        'offer_percentage' => 'decimal:2',
        'offer_price'      => 'decimal:2',
        'final_price'      => 'decimal:2',
        'selected_display' => 'boolean',
        'created_date'     => 'datetime',
        'updated_date'     => 'datetime',
    ];

    /**
     * Rate belongs to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Rate belongs to UOM
     */
    public function uom()
    {
        return $this->belongsTo(UomMaster::class, 'uom_id');
    }

    /**
     * Created by admin
     */
    public function createdBy()
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    /**
     * Updated by admin
     */
    public function updatedBy()
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }
}
