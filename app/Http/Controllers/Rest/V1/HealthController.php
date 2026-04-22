<?php

namespace App\Http\Controllers\Rest\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
