<?php

namespace App\Actions\Reports;

use App\Models\Group;
use App\Models\Ticket;
use App\Support\ManagerScope;
use Illuminate\Support\Carbon;

final class GroupPerformanceReportAction
{
    public function handle(array $data = []): array
    {
        $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::now()->subDays(30);
        $to   = isset($data['to'])   ? Carbon::parse($data['to'])   : Carbon::now();

        $q = Ticket::query()
            ->whereBetween('fd_created_at', [$from, $to])
            ->whereNotNull('group_id');
        ManagerScope::applyToTickets($q);

        $rows = $q->selectRaw('
                group_id,
                COUNT(*) as assigned,
                SUM(CASE WHEN status = 4 OR status = 5 THEN 1 ELSE 0 END) as resolved
            ')
            ->groupBy('group_id')
            ->get();

        $groups = Group::whereIn('id', $rows->pluck('group_id')->all())->get()->keyBy('id');
        $out = $rows->map(fn ($r) => [
            'group'          => ['id' => $r->group_id, 'name' => $groups[$r->group_id]->name ?? '—'],
            'assigned'       => (int) $r->assigned,
            'resolved'       => (int) $r->resolved,
            'avg_frt'        => null,
            'avg_resolution' => null,
            'csat_avg'       => null,
        ])->all();

        return ['rows' => $out, 'from' => $from->toIso8601String(), 'to' => $to->toIso8601String()];
    }
}
