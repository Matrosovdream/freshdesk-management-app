<?php

namespace App\Http\Controllers\Api\V1\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Portal\Auth\MagicLinkConsumeRequest;
use App\Http\Requests\Api\V1\Portal\Auth\MagicLinkSendRequest;
use Illuminate\Http\JsonResponse;

class MagicLinkController extends Controller
{
    public function send(MagicLinkSendRequest $request, \App\Actions\Portal\Auth\SendMagicLinkAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function consume(MagicLinkConsumeRequest $request, \App\Actions\Portal\Auth\ConsumeMagicLinkAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
