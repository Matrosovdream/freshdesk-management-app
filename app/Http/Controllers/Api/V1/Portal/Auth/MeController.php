<?php

namespace App\Http\Controllers\Api\V1\Portal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request, \App\Actions\Portal\Auth\GetCurrentPortalUserAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
