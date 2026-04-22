<?php

namespace App\Http\Controllers\Api\V1\Admin\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupPerformanceController extends Controller
{
    public function __invoke(Request $request, \App\Actions\Reports\GroupPerformanceReportAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
