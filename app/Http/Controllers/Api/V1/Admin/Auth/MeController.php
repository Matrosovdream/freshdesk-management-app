<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request, \App\Actions\Auth\GetCurrentUserAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
