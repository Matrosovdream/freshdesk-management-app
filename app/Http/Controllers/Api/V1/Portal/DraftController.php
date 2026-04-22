<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function show(Request $request, \App\Actions\Portal\Drafts\LoadDraftAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function save(Request $request, \App\Actions\Portal\Drafts\SaveDraftAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function clear(Request $request, \App\Actions\Portal\Drafts\ClearDraftAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
