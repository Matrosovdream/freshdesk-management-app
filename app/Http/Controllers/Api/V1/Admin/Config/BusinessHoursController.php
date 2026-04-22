<?php

namespace App\Http\Controllers\Api\V1\Admin\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessHoursController extends Controller
{
    public function index(Request $request, \App\Actions\Config\ListBusinessHoursAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
