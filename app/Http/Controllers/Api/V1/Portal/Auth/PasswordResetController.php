<?php

namespace App\Http\Controllers\Api\V1\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Portal\Auth\ForgotRequest;
use App\Http\Requests\Api\V1\Portal\Auth\ResetRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function sendLink(ForgotRequest $request, \App\Actions\Portal\Auth\SendPortalResetLinkAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function reset(ResetRequest $request, \App\Actions\Portal\Auth\ResetPortalPasswordAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
