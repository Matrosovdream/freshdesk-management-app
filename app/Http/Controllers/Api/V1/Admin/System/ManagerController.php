<?php

namespace App\Http\Controllers\Api\V1\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\System\Managers\SetScopeRequest;
use App\Http\Requests\Api\V1\Admin\System\Managers\StoreRequest;
use App\Http\Requests\Api\V1\Admin\System\Managers\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function index(Request $request, \App\Actions\System\Managers\ListManagersAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\System\Managers\CreateManagerAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function show(Request $request, \App\Actions\System\Managers\GetManagerAction $action, int $manager): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $manager]))]);
    }

    public function update(UpdateRequest $request, \App\Actions\System\Managers\UpdateManagerAction $action, int $manager): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $manager]))]);
    }

    public function destroy(Request $request, \App\Actions\System\Managers\DeleteManagerAction $action, int $manager): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $manager]))]);
    }

    public function setScope(SetScopeRequest $request, \App\Actions\System\Managers\SetManagerScopeAction $action, int $manager): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $manager]))]);
    }
}
