<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerGroupScope extends Model
{
    protected $table = 'manager_group_scopes';

    protected $fillable = ['user_id', 'group_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
