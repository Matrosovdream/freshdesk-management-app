<?php

namespace App\Http\Controllers\Api\V1\Portal\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'allowPublicRegistration' => false,
                'requireCaptcha' => false,
                'csatOnResolve' => false,
            ],
        ]);
    }
}
