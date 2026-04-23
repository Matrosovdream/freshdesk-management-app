<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['last_login_at', 'avatar_url'];

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

    public function getLastLoginAtAttribute()
    {
        return $this->fd_updated_at;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->payload['avatar']['avatar_url'] ?? null;
    }
}
