<?php

namespace App\Http\Controllers\Api\V1\Portal\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, \App\Actions\Portal\Config\ListProductsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
