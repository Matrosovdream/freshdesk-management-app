<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'active'          => 'boolean',
        'view_all_tickets'=> 'boolean',
        'other_emails'    => 'array',
        'other_companies' => 'array',
        'tags'            => 'array',
        'custom_fields'   => 'array',
        'payload'         => 'array',
        'fd_created_at'   => 'datetime',
        'fd_updated_at'   => 'datetime',
        'synced_at'       => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }
}
