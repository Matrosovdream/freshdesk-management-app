<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalDraft extends Model
{
    protected $fillable = ['user_id', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
