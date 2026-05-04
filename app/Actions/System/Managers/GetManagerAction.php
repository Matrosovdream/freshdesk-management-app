<?php

namespace App\Actions\System\Managers;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetManagerAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        
        $u = User::with(['roles', 'managerGroups'])->find($id);
        if (! $u) throw new NotFoundHttpException('Manager not found.');
        $arr = $u->toArray();
        $arr['assigned_groups'] = $arr['manager_groups'] ?? [];
        unset($arr['manager_groups']);

        return $arr;
    }
}
