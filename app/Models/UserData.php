<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
  
    protected $table = 'user_data';

    protected $fillable = [
        'user',
        'created_by',
        'address',
        'city',
        'province',
        'postal_code',
        'first_name',
        'last_name',
        'middle_name',
        'phone',
        'id_type',
        'id_number',
        'date_of_birth',
        'gender',
        'profile_photo_path',
        'job_title',
        'department',
        'employment_status',
        'status',
        'last_login',
    ];
// ── Custom Attributes ────────────────────────────────────────────────────
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code,
        ]);
        return implode(', ', $parts);
    }


    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]));
    }
    public function getInitialsAttribute(): string
    {
        $parts = array_filter(explode(' ', $this->full_name));

        return implode('', array_map(
            fn($part) => strtoupper($part[0]),
            $parts
        ));
    }
}
