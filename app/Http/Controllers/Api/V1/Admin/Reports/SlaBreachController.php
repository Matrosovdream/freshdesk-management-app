<?php

namespace App\Http\Controllers\Api\V1\Admin\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlaBreachController extends Controller
{
    public function __invoke(Request $request, \App\Actions\Reports\SlaBreachReportAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
