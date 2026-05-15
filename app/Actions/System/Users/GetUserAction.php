<?php

namespace App\Actions\System\Users;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetUserAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);

        $u = User::with(['roles:id,slug,name', 'managerGroups'])->find($id);
        if (! $u) throw new NotFoundHttpException('User not found.');

        $hasPin = !empty($u->getAttribute('pin'));
        $arr = $u->toArray();
        $arr['assigned_groups'] = $arr['manager_groups'] ?? [];
        $arr['has_pin'] = $hasPin;
        unset($arr['manager_groups'], $arr['pin']);

        return $arr;
    }
}
