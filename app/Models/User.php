<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'freshdesk_contact_id',
        'last_login_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function managerGroups()
    {
        return $this->belongsToMany(Group::class, 'manager_group_scopes')->withTimestamps();
    }

    public function freshdeskContact()
    {
        return $this->belongsTo(Contact::class, 'freshdesk_contact_id', 'freshdesk_id');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function rights(): array
    {
        return $this->roles
            ->flatMap->rights
            ->pluck('right')
            ->unique()
            ->values()
            ->all();
    }

    public function hasRight(string $right): bool
    {
        return in_array($right, $this->rights(), true);
    }
}
