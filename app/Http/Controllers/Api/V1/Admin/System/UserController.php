<?php

namespace App\Http\Controllers\Api\V1\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\System\Users\StoreRequest;
use App\Http\Requests\Api\V1\Admin\System\Users\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, \App\Actions\System\Users\ListUsersAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\System\Users\CreateUserAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function show(Request $request, \App\Actions\System\Users\GetUserAction $action, int $user): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $user]))]);
    }

    public function update(UpdateRequest $request, \App\Actions\System\Users\UpdateUserAction $action, int $user): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $user]))]);
    }

    public function destroy(Request $request, \App\Actions\System\Users\DeleteUserAction $action, int $user): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $user]))]);
    }
}
