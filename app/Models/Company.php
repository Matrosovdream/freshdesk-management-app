<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'domains'        => 'array',
        'custom_fields'  => 'array',
        'payload'        => 'array',
        'renewal_date'   => 'date',
        'fd_created_at'  => 'datetime',
        'fd_updated_at'  => 'datetime',
        'synced_at'      => 'datetime',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
