<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Property;
use App\Models\User;

class Unit extends Model
{
    protected $fillable = [
        'property_id',
        'unit_number',
        'rent_price',
        'is_occupied',
        'tenant_id',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}