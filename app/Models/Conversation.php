<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'private'       => 'boolean',
        'incoming'      => 'boolean',
        'to_emails'     => 'array',
        'cc_emails'     => 'array',
        'bcc_emails'    => 'array',
        'attachments'   => 'array',
        'payload'       => 'array',
        'fd_created_at' => 'datetime',
        'fd_updated_at' => 'datetime',
        'synced_at'     => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
