<?php

namespace App\Http\Controllers\Rest\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rest\V1\Webhooks\Freshdesk\ContactUpdatedRequest;
use App\Http\Requests\Rest\V1\Webhooks\Freshdesk\TicketCreatedRequest;
use App\Http\Requests\Rest\V1\Webhooks\Freshdesk\TicketRepliedRequest;
use App\Http\Requests\Rest\V1\Webhooks\Freshdesk\TicketUpdatedRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreshdeskController extends Controller
{
    public function handle(Request $request, \App\Actions\Webhooks\Freshdesk\RouteWebhookAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function ticketCreated(TicketCreatedRequest $request, \App\Actions\Webhooks\Freshdesk\HandleTicketCreatedAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function ticketUpdated(TicketUpdatedRequest $request, \App\Actions\Webhooks\Freshdesk\HandleTicketUpdatedAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function ticketReplied(TicketRepliedRequest $request, \App\Actions\Webhooks\Freshdesk\HandleTicketRepliedAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function contactUpdated(ContactUpdatedRequest $request, \App\Actions\Webhooks\Freshdesk\HandleContactUpdatedAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
