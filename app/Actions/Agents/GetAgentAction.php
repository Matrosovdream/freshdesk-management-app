<?php

namespace App\Actions\Agents;

use App\Models\Agent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetAgentAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);

        $a = Agent::find($id);
        if (! $a) throw new NotFoundHttpException('Agent not found.');
        
        return $a->toArray();
    }
}
