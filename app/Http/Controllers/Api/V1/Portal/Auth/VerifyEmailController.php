<?php

namespace App\Http\Controllers\Api\V1\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Portal\Auth\VerifyRequest;
use Illuminate\Http\JsonResponse;

class VerifyEmailController extends Controller
{
    public function store(VerifyRequest $request, \App\Actions\Portal\Auth\VerifyEmailAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
