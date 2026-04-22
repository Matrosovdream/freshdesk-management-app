<?php

namespace App\Http\Controllers\Rest\V1\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
