<?php

namespace App\Http\Controllers\Api\V1\Admin\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request, \App\Actions\Config\ListRolesAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
