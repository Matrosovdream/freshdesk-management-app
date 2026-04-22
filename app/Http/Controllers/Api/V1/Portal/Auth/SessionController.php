<?php

namespace App\Http\Controllers\Api\V1\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Portal\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function store(LoginRequest $request, \App\Actions\Portal\Auth\PortalLoginAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function destroy(Request $request, \App\Actions\Portal\Auth\PortalLogoutAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function logoutOthers(Request $request, \App\Actions\Portal\Auth\PortalLogoutAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all() + ['others' => true])]);
    }
}
