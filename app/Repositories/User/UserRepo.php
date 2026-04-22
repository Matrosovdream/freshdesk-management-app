<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\AbstractRepo;

class UserRepo extends AbstractRepo
{
    protected $withRelations = ['roles', 'roles.rights'];

    public function __construct()
    {
        $this->model = new User();
    }

    public function getByEmail(string $email)
    {
        $item = $this->model
            ->where('email', $email)
            ->with($this->withRelations)
            ->first();

        return $this->mapItem($item);
    }

    public function getByRoleSlug(string $slug, $paginate = 20)
    {
        $query = $this->model
            ->with($this->withRelations)
            ->whereHas('roles', fn ($q) => $q->where('slug', $slug));

        return $this->mapItems($query->paginate($paginate));
    }

    public function getByFreshdeskContactId(int $fdContactId)
    {
        $item = $this->model
            ->where('freshdesk_contact_id', $fdContactId)
            ->with($this->withRelations)
            ->first();

        return $this->mapItem($item);
    }

    public function touchLastLogin(int $id): void
    {
        $this->model->where('id', $id)->update(['last_login_at' => now()]);
    }

    public function syncRoles(int $userId, array $roleIds): void
    {
        $user = $this->model->find($userId);
        $user?->roles()->sync($roleIds);
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'                   => $item->id,
            'email'                => $item->email,
            'name'                 => $item->name,
            'phone'                => $item->phone,
            'avatar'               => $item->avatar,
            'is_active'            => (bool) $item->is_active,
            'freshdesk_contact_id' => $item->freshdesk_contact_id,
            'last_login_at'        => $item->last_login_at,
            'roles'                => $item->relationLoaded('roles')
                ? $item->roles->map(fn ($r) => [
                    'id'   => $r->id,
                    'slug' => $r->slug,
                    'name' => $r->name,
                ])->values()->toArray()
                : [],
            'rights'               => $item->relationLoaded('roles') ? $item->rights() : [],
            'created_at'           => $item->created_at,
            'Model'                => $item,
        ];
    }
}
