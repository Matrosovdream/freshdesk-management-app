<?php

namespace App\Http\Controllers\Api\V1\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\System\ApiKeys\CreateApiKeyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request, \App\Actions\System\ApiKeys\ListApiKeysAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(CreateApiKeyRequest $request, \App\Actions\System\ApiKeys\CreateApiKeyAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function rotate(Request $request, \App\Actions\System\ApiKeys\RotateApiKeyAction $action, int $apiKey): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $apiKey]))]);
    }

    public function revoke(Request $request, \App\Actions\System\ApiKeys\RevokeApiKeyAction $action, int $apiKey): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $apiKey]))]);
    }
}
