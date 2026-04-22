<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, \App\Actions\AuditLogs\ListAuditLogsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
