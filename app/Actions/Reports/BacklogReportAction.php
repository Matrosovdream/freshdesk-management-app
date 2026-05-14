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
        $userFrom = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null;
        $userTo   = !empty($data['to'])   ? Carbon::parse($data['to'])->endOfDay()     : null;

        $buckets = [
            '0_1d' => [$now->copy()->subDays(1),  $now],
            '1_3d' => [$now->copy()->subDays(3),  $now->copy()->subDays(1)],
            '3_7d' => [$now->copy()->subDays(7),  $now->copy()->subDays(3)],
            '7d+'  => [null,                      $now->copy()->subDays(7)],
        ];

        $rows = [];
        foreach ($buckets as $label => [$from, $to]) {
            $lower = $userFrom && (! $from || $userFrom->gt($from)) ? $userFrom : $from;
            $upper = $userTo   && (! $to   || $userTo->lt($to))     ? $userTo   : $to;

            $q = Ticket::query()->whereIn('status', [2, 3]);
            if ($lower) $q->where('fd_created_at', '>=', $lower);
            if ($upper) $q->where('fd_created_at', '<=', $upper);
            ManagerScope::applyToTickets($q);
            $rows[] = ['bucket' => $label, 'count' => $q->count()];
        }

        return ['rows' => $rows];
    }
}
