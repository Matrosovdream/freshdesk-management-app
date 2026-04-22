<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Agents\BulkCreateRequest;
use App\Http\Requests\Api\V1\Admin\Agents\StoreRequest;
use App\Http\Requests\Api\V1\Admin\Agents\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request, \App\Actions\Agents\ListAgentsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\Agents\CreateAgentAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function show(Request $request, \App\Actions\Agents\GetAgentAction $action, int $agent): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $agent]))]);
    }

    public function update(UpdateRequest $request, \App\Actions\Agents\UpdateAgentAction $action, int $agent): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $agent]))]);
    }

    public function destroy(Request $request, \App\Actions\Agents\DeleteAgentAction $action, int $agent): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $agent]))]);
    }

    public function bulkCreate(BulkCreateRequest $request, \App\Actions\Agents\BulkCreateAgentsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
