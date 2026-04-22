<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Portal\Requests\RateRequest;
use App\Http\Requests\Api\V1\Portal\Requests\ReplyRequest;
use App\Http\Requests\Api\V1\Portal\Requests\StoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index(Request $request, \App\Actions\Portal\Requests\ListRequestsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\Portal\Requests\SubmitRequestAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function show(Request $request, \App\Actions\Portal\Requests\ShowRequestAction $action, string $id): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all() + ['id' => $id])]);
    }

    public function reply(ReplyRequest $request, \App\Actions\Portal\Requests\ReplyToRequestAction $action, string $id): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated() + ['id' => $id])]);
    }

    public function resolve(Request $request, \App\Actions\Portal\Requests\ResolveRequestAction $action, string $id): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all() + ['id' => $id])]);
    }

    public function reopen(Request $request, \App\Actions\Portal\Requests\ReopenRequestAction $action, string $id): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all() + ['id' => $id])]);
    }

    public function rate(RateRequest $request, \App\Actions\Portal\Requests\RateRequestAction $action, string $id): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated() + ['id' => $id])]);
    }
}
