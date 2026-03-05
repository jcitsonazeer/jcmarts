<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferDetail extends Model
{
    protected $table = 'offer_details';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'offer_name',
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

    public function createdBy()
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }

    public function offerProducts()
    {
        return $this->hasMany(OfferProduct::class, 'offer_id');
    }
}
