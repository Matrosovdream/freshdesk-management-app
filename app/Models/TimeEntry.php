<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $table = 'time_entries';

    protected $guarded = ['id'];

    protected $casts = [
        'billable'      => 'boolean',
        'timer_running' => 'boolean',
        'payload'       => 'array',
        'executed_at'   => 'datetime',
        'start_time'    => 'datetime',
        'fd_created_at' => 'datetime',
        'fd_updated_at' => 'datetime',
        'synced_at'     => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
