<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $guarded = ['id'];

    protected $casts = [
        'payload_before' => 'array',
        'payload_after'  => 'array',
        'meta'           => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
