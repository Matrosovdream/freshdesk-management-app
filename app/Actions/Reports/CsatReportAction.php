<?php

namespace App\Actions\Reports;

final class CsatReportAction
{
    public function handle(array $data = []): array
    {
        // Satisfaction ratings live in the ticket payload JSON today; surface
        // an empty shell until a dedicated survey table lands in a later step.
        return [
            'distribution' => ['love' => 0, 'like' => 0, 'neutral' => 0, 'dislike' => 0, 'hate' => 0],
            'comments'     => [],
        ];
    }
}
