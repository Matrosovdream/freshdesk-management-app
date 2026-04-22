<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Tickets\AssignRequest;
use App\Http\Requests\Api\V1\Admin\Tickets\ForwardRequest;
use App\Http\Requests\Api\V1\Admin\Tickets\MergeRequest;
use App\Http\Requests\Api\V1\Admin\Tickets\OutboundEmailRequest;
use App\Http\Requests\Api\V1\Admin\Tickets\StoreRequest;
use App\Http\Requests\Api\V1\Admin\Tickets\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request, \App\Actions\Tickets\ListTicketsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\Tickets\CreateTicketAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function show(Request $request, \App\Actions\Tickets\GetTicketAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $ticket]))]);
    }

    public function update(UpdateRequest $request, \App\Actions\Tickets\UpdateTicketAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $ticket]))]);
    }

    public function destroy(Request $request, \App\Actions\Tickets\DeleteTicketAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $ticket]))]);
    }

    public function restore(Request $request, \App\Actions\Tickets\RestoreTicketAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $ticket]))]);
    }

    public function merge(MergeRequest $request, \App\Actions\Tickets\MergeTicketsAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $ticket]))]);
    }

    public function forward(ForwardRequest $request, \App\Actions\Tickets\ForwardTicketAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $ticket]))]);
    }

    public function outboundEmail(OutboundEmailRequest $request, \App\Actions\Tickets\CreateOutboundEmailAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function assign(AssignRequest $request, \App\Actions\Tickets\AssignTicketAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $ticket]))]);
    }
}
