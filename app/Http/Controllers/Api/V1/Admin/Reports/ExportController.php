<?php

namespace App\Http\Controllers\Api\V1\Admin\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function store(Request $request, \App\Actions\Reports\ExportReportAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
