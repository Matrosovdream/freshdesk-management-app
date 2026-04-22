<?php

namespace App\Http\Controllers\Api\V1\Admin\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\System\Settings\UpdateSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request, \App\Actions\System\Settings\GetSettingsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function update(UpdateSettingsRequest $request, \App\Actions\System\Settings\UpdateSettingsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
