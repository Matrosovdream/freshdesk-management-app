<?php

namespace App\Actions\Reports;

use App\Models\Ticket;
use App\Support\ManagerScope;
use Illuminate\Support\Carbon;

final class SlaBreachReportAction
{
    public function handle(array $data = []): array
    {
        $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::now()->subDays(30);
        $to   = isset($data['to'])   ? Carbon::parse($data['to'])   : Carbon::now();

        $q = Ticket::query()
            ->whereNotNull('due_by')
            ->where('due_by', '<', Carbon::now())
            ->whereIn('status', [2, 3])
            ->whereBetween('due_by', [$from, $to])
            ->with(['responder', 'group']);
        ManagerScope::applyToTickets($q);

        $rows = $q->orderBy('due_by')->limit(500)->get()->map(fn ($t) => [
            'ticket_id'    => $t->id,
            'display_id'   => $t->freshdesk_id,
            'subject'      => $t->subject,
            'breach_type'  => 'due_by',
            'breached_at'  => optional($t->due_by)->toIso8601String(),
            'agent'        => $t->responder ? ['id' => $t->responder->id, 'name' => $t->responder->name] : null,
            'group'        => $t->group ? ['id' => $t->group->id, 'name' => $t->group->name] : null,
        ])->all();

        return ['rows' => $rows, 'from' => $from->toIso8601String(), 'to' => $to->toIso8601String()];
    }
}
