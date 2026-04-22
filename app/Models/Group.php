<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'agent_ids'          => 'array',
        'auto_ticket_assign' => 'boolean',
        'payload'            => 'array',
        'fd_created_at'      => 'datetime',
        'fd_updated_at'      => 'datetime',
        'synced_at'          => 'datetime',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'manager_group_scopes')->withTimestamps();
    }
}
