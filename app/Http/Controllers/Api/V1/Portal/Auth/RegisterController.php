<?php

namespace App\Http\Controllers\Api\V1\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Portal\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request, \App\Actions\Portal\Auth\PortalRegisterAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
