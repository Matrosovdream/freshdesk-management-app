<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'to_emails'       => 'array',
        'cc_emails'       => 'array',
        'fwd_emails'      => 'array',
        'reply_cc_emails' => 'array',
        'tags'            => 'array',
        'custom_fields'   => 'array',
        'payload'         => 'array',
        'spam'            => 'boolean',
        'is_escalated'    => 'boolean',
        'fr_escalated'    => 'boolean',
        'due_by'          => 'datetime',
        'fr_due_by'       => 'datetime',
        'fd_created_at'   => 'datetime',
        'fd_updated_at'   => 'datetime',
        'synced_at'       => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(Contact::class, 'requester_id');
    }

    public function responder()
    {
        return $this->belongsTo(Agent::class, 'responder_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
