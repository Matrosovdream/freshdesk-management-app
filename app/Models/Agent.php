<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'available'      => 'boolean',
        'occasional'     => 'boolean',
        'group_ids'      => 'array',
        'role_ids'       => 'array',
        'skill_ids'      => 'array',
        'payload'        => 'array',
        'fd_created_at'  => 'datetime',
        'fd_updated_at'  => 'datetime',
        'synced_at'      => 'datetime',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'responder_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
