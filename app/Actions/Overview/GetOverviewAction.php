<?php

namespace App\Actions\Overview;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Ticket;
use App\Support\ManagerScope;
use Illuminate\Support\Carbon;

final class GetOverviewAction
{
    public function handle(array $data = []): array
    {
        $now  = Carbon::now();
        $sod  = $now->copy()->startOfDay();
        $week = $now->copy()->subDays(7);

        $base = fn () => ManagerScope::applyToTickets(Ticket::query());

        $open        = (clone $base())->where('status', 2)->count();
        $pending     = (clone $base())->where('status', 3)->count();
        $overdue     = (clone $base())->whereNotNull('due_by')->where('due_by', '<', $now)->whereIn('status', [2, 3])->count();
        $unassigned  = (clone $base())->whereNull('responder_id')->whereIn('status', [2, 3])->count();
        $slaBreaches = (clone $base())->whereNotNull('due_by')
                            ->whereBetween('due_by', [$sod, $now])
                            ->whereIn('status', [2, 3])->count();

        // Avg first-response time over the last 7 days — compute in PHP so
        // the query is portable across sqlite/mysql/postgres.
        $ticketIds = (clone $base())->pluck('id')->all();
        $avgFrtSecs = null;
        if (!empty($ticketIds)) {
            $pairs = Conversation::query()
                ->whereIn('ticket_id', $ticketIds)
                ->where('fd_created_at', '>=', $week)
                ->orderBy('ticket_id')
                ->orderBy('fd_created_at')
                ->get(['ticket_id', 'fd_created_at'])
                ->groupBy('ticket_id')
                ->map(fn ($group) => $group->first()->fd_created_at);

            if ($pairs->isNotEmpty()) {
                $ticketCreated = Ticket::whereIn('id', $pairs->keys())
                    ->pluck('fd_created_at', 'id');

                $diffs = [];
                foreach ($pairs as $tid => $firstReply) {
                    $created = $ticketCreated[$tid] ?? null;
                    if ($firstReply && $created) {
                        $diffs[] = $firstReply->diffInSeconds($created);
                    }
                }
                if (!empty($diffs)) $avgFrtSecs = (int) (array_sum($diffs) / count($diffs));
            }
        }
        $avgFrt = $avgFrtSecs ? sprintf('%dh %02dm', intdiv($avgFrtSecs, 3600), intdiv($avgFrtSecs % 3600, 60)) : null;

        $topAgents = (clone $base())
            ->where('status', 4)
            ->where('fd_updated_at', '>=', $week)
            ->whereNotNull('responder_id')
            ->selectRaw('responder_id, COUNT(*) as resolved')
            ->groupBy('responder_id')
            ->orderByDesc('resolved')
            ->limit(5)
            ->get();
        $agentsById = Agent::whereIn('id', $topAgents->pluck('responder_id')->all())->get()->keyBy('id');
        $topAgents = $topAgents->map(fn ($r) => [
            'id'       => $r->responder_id,
            'name'     => $agentsById[$r->responder_id]->name ?? '—',
            'resolved' => (int) $r->resolved,
        ])->all();

        $topCompanies = (clone $base())
            ->whereIn('status', [2, 3])
            ->whereNotNull('company_id')
            ->selectRaw('company_id, COUNT(*) as open')
            ->groupBy('company_id')
            ->orderByDesc('open')
            ->limit(5)
            ->get();
        $companiesById = Company::whereIn('id', $topCompanies->pluck('company_id')->all())->get()->keyBy('id');
        $topCompanies = $topCompanies->map(fn ($r) => [
            'id'   => $r->company_id,
            'name' => $companiesById[$r->company_id]->name ?? '—',
            'open' => (int) $r->open,
        ])->all();

        $activity = AuditLog::latest()->limit(20)->get()->map(fn ($e) => [
            'id'      => $e->id,
            'when'    => optional($e->created_at)->diffForHumans(),
            'summary' => $e->action.($e->target_type ? ' '.$e->target_type.(($e->target_id) ? ' #'.$e->target_id : '') : ''),
        ])->all();

        return [
            'kpis' => [
                'open'                => $open,
                'pending'             => $pending,
                'overdue'             => $overdue,
                'unassigned'          => $unassigned,
                'sla_breaches_today'  => $slaBreaches,
                'avg_frt_7d'          => $avgFrt,
            ],
            'top_agents'    => $topAgents,
            'top_companies' => $topCompanies,
            'activity'      => $activity,
        ];
    }
}
