<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleRight extends Model
{
    protected $table = 'role_rights';

    protected $fillable = ['role_id', 'right', 'group'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
