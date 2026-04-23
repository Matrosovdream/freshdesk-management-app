<?php

namespace App\Actions\Reports;

use App\Models\Ticket;
use App\Support\ManagerScope;
use Illuminate\Support\Carbon;

final class VolumeReportAction
{
    public function handle(array $data = []): array
    {
        $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::now()->subDays(30);
        $to   = isset($data['to'])   ? Carbon::parse($data['to'])   : Carbon::now();

        $createdQ = Ticket::query()->whereBetween('fd_created_at', [$from, $to]);
        ManagerScope::applyToTickets($createdQ);
        $created = $createdQ
            ->selectRaw('DATE(fd_created_at) as day, COUNT(*) as n')
            ->groupBy('day')->orderBy('day')->get();

        $resolvedQ = Ticket::query()
            ->whereBetween('fd_updated_at', [$from, $to])
            ->whereIn('status', [4, 5]);
        ManagerScope::applyToTickets($resolvedQ);
        $resolved = $resolvedQ
            ->selectRaw('DATE(fd_updated_at) as day, COUNT(*) as n')
            ->groupBy('day')->orderBy('day')->get();

        return [
            'created'  => $created->map(fn ($r) => ['day' => (string) $r->day, 'n' => (int) $r->n])->all(),
            'resolved' => $resolved->map(fn ($r) => ['day' => (string) $r->day, 'n' => (int) $r->n])->all(),
            'from' => $from->toIso8601String(),
            'to'   => $to->toIso8601String(),
        ];
    }
}
