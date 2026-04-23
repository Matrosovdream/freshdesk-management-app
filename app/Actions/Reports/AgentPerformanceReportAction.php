<?php

namespace App\Actions\Reports;

use App\Models\Agent;
use App\Models\Ticket;
use App\Support\ManagerScope;
use Illuminate\Support\Carbon;

final class AgentPerformanceReportAction
{
    public function handle(array $data = []): array
    {
        $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::now()->subDays(30);
        $to   = isset($data['to'])   ? Carbon::parse($data['to'])   : Carbon::now();

        $q = Ticket::query()
            ->whereBetween('fd_created_at', [$from, $to])
            ->whereNotNull('responder_id');
        ManagerScope::applyToTickets($q);

        $rows = $q->selectRaw('
                responder_id,
                COUNT(*) as assigned,
                SUM(CASE WHEN status = 4 OR status = 5 THEN 1 ELSE 0 END) as resolved
            ')
            ->groupBy('responder_id')
            ->get();

        $agents = Agent::whereIn('id', $rows->pluck('responder_id')->all())->get()->keyBy('id');

        $out = $rows->map(fn ($r) => [
            'agent'          => ['id' => $r->responder_id, 'name' => $agents[$r->responder_id]->name ?? '—'],
            'assigned'       => (int) $r->assigned,
            'resolved'       => (int) $r->resolved,
            'avg_frt'        => null,
            'avg_resolution' => null,
            'csat_avg'       => null,
        ])->all();

        return ['rows' => $out, 'from' => $from->toIso8601String(), 'to' => $to->toIso8601String()];
    }
}
