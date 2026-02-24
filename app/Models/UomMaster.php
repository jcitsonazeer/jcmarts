<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UomMaster extends Model
{
    protected $table = 'uom_master';

    protected $primaryKey = 'id';

    public $timestamps = false; // Using created_date / updated_date manually

    protected $fillable = [
        'primary_uom',
        'secondary_uom',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

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

    /**
     * UOM used in Rate Master
     */
    public function rates()
    {
        return $this->hasMany(RateMaster::class, 'uom_id');
    }
}
