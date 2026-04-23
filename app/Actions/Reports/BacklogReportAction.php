<?php

namespace App\Actions\Reports;

use App\Models\Ticket;
use App\Support\ManagerScope;
use Illuminate\Support\Carbon;

final class BacklogReportAction
{
    public function handle(array $data = []): array
    {
        $now = Carbon::now();
        $buckets = [
            '0_1d' => [$now->copy()->subDays(1),  $now],
            '1_3d' => [$now->copy()->subDays(3),  $now->copy()->subDays(1)],
            '3_7d' => [$now->copy()->subDays(7),  $now->copy()->subDays(3)],
            '7d+'  => [null,                      $now->copy()->subDays(7)],
        ];

        $rows = [];
        foreach ($buckets as $label => [$from, $to]) {
            $q = Ticket::query()->whereIn('status', [2, 3]);
            if ($from) $q->where('fd_created_at', '>=', $from);
            if ($to)   $q->where('fd_created_at', '<=', $to);
            ManagerScope::applyToTickets($q);
            $rows[] = ['bucket' => $label, 'count' => $q->count()];
        }
        return ['rows' => $rows];
    }
}
