<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Tickets\BulkDeleteRequest;
use App\Http\Requests\Api\V1\Admin\Tickets\BulkUpdateRequest;
use Illuminate\Http\JsonResponse;

class TicketBulkController extends Controller
{
    public function update(BulkUpdateRequest $request, \App\Actions\Tickets\BulkUpdateTicketsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function destroy(BulkDeleteRequest $request, \App\Actions\Tickets\BulkDeleteTicketsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
