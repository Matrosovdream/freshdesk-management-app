<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function store(LoginRequest $request, \App\Actions\Auth\LoginAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function destroy(Request $request, \App\Actions\Auth\LogoutAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
