<?php

namespace App\Actions\Reports;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ExportReportAction
{
    public function handle(array $data = []): array
    {
        // Report flag comes from the route param and is merged into $data in the controller.
        $report = (string) ($data['report'] ?? request()->route('report') ?? 'report');

        $actionClass = match ($report) {
            'backlog'           => BacklogReportAction::class,
            'agent-performance' => AgentPerformanceReportAction::class,
            'group-performance' => GroupPerformanceReportAction::class,
            'sla-breaches'      => SlaBreachReportAction::class,
            'volume'            => VolumeReportAction::class,
            'csat'              => CsatReportAction::class,
            default             => null,
        };
        if (! $actionClass) return ['download_url' => null];

        $payload = app($actionClass)->handle((array) ($data['filters'] ?? []));

        $name = "exports/{$report}-".Str::uuid().'.csv';
        $disk = Storage::disk('public');
        $tmp = tmpfile();

        $rows = $payload['rows'] ?? $payload['created'] ?? [];
        if (! empty($rows) && is_array($rows[0])) {
            fputcsv($tmp, array_keys(\Illuminate\Support\Arr::dot($rows[0])));
            foreach ($rows as $r) {
                $flat = \Illuminate\Support\Arr::dot($r);
                fputcsv($tmp, array_values($flat));
            }
        } else {
            fputcsv($tmp, ['key', 'value']);
            foreach (\Illuminate\Support\Arr::dot($payload) as $k => $v) {
                fputcsv($tmp, [$k, is_scalar($v) ? $v : json_encode($v)]);
            }
        }

        fseek($tmp, 0);
        $disk->put($name, stream_get_contents($tmp));
        fclose($tmp);

        return ['download_url' => $disk->url($name)];
    }
}
