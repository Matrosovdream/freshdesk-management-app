<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }

    public function rights()
    {
        return $this->hasMany(RoleRight::class);
    }
}
