<?php

namespace App\Http\Controllers\Api\V1\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\System\Freshdesk\UpdateConnectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreshdeskConnectionController extends Controller
{
    public function show(Request $request, \App\Actions\System\Freshdesk\GetConnectionAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function update(UpdateConnectionRequest $request, \App\Actions\System\Freshdesk\UpdateConnectionAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function test(Request $request, \App\Actions\System\Freshdesk\TestConnectionAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
