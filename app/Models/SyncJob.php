<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncJob extends Model
{
    protected $table = 'sync_jobs';

    protected $guarded = ['id'];

    protected $casts = [
        'meta'        => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];
}
