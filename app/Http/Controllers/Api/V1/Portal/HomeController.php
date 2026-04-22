<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, \App\Actions\Portal\Home\GetHomeAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
