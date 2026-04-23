<?php

namespace App\Actions\Agents;

use App\Models\Agent;
use App\Support\AuditWriter;

final class DeleteAgentAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $a = Agent::findOrFail($id);
        $a->delete();
        AuditWriter::log('agent.deleted', 'Agent', $id);
        return ['id' => $id, 'deleted' => true];
    }
}
