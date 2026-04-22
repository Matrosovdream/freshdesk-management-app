<?php

namespace App\Http\Controllers\Api\V1\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncJobController extends Controller
{
    public function index(Request $request, \App\Actions\System\SyncJobs\ListSyncJobsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function run(Request $request, \App\Actions\System\SyncJobs\RunSyncJobAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function fullResync(Request $request, \App\Actions\System\SyncJobs\FullResyncAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
