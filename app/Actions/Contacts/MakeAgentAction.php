<?php

namespace App\Actions\Contacts;

use App\Models\Agent;
use App\Models\Contact;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\DB;

final class MakeAgentAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Contact::findOrFail($id);

        $agent = DB::transaction(function () use ($c) {
            $max = (int) Agent::max('freshdesk_id');
            return Agent::create([
                'freshdesk_id'  => $max > 0 ? $max + 1 : 1_000_000,
                'email'         => $c->email,
                'name'          => $c->name,
                'language'      => $c->language,
                'time_zone'     => $c->time_zone,
                'available'     => true,
                'occasional'    => true,
                'type'          => 'support_agent',
                'ticket_scope'  => 1,
                'group_ids'     => [],
                'role_ids'      => [],
                'skill_ids'     => [],
                'fd_created_at' => now(),
                'fd_updated_at' => now(),
            ]);
        });

        AuditWriter::log('contact.made_agent', 'Contact', $c->id, [], ['agent_id' => $agent->id]);
        return $agent->toArray();
    }
}
