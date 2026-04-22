<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function index(Request $request, \App\Actions\Overview\GetOverviewAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function refresh(Request $request, \App\Actions\Overview\RefreshOverviewAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
