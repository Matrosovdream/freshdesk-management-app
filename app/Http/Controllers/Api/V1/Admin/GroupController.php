<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Groups\StoreRequest;
use App\Http\Requests\Api\V1\Admin\Groups\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request, \App\Actions\Groups\ListGroupsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\Groups\CreateGroupAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function update(UpdateRequest $request, \App\Actions\Groups\UpdateGroupAction $action, int $group): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $group]))]);
    }

    public function destroy(Request $request, \App\Actions\Groups\DeleteGroupAction $action, int $group): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $group]))]);
    }
}
