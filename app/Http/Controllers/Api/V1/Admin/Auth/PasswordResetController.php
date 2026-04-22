<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Auth\ForgotRequest;
use App\Http\Requests\Api\V1\Admin\Auth\ResetRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function sendLink(ForgotRequest $request, \App\Actions\Auth\SendPasswordResetLinkAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function reset(ResetRequest $request, \App\Actions\Auth\ResetPasswordAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
