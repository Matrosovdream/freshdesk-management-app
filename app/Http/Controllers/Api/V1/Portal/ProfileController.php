<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Portal\Profile\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Portal\Profile\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request, \App\Actions\Portal\Profile\ShowPortalProfileAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function update(UpdateRequest $request, \App\Actions\Portal\Profile\UpdatePortalProfileAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function changePassword(ChangePasswordRequest $request, \App\Actions\Portal\Profile\ChangePortalPasswordAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function destroy(Request $request, \App\Actions\Portal\Profile\DeletePortalAccountAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
