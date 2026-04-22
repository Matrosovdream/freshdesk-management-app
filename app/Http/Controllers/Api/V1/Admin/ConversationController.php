<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Conversations\NoteRequest;
use App\Http\Requests\Api\V1\Admin\Conversations\ReplyRequest;
use App\Http\Requests\Api\V1\Admin\Conversations\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request, \App\Actions\Conversations\ListConversationsAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['ticket_id' => $ticket]))]);
    }

    public function reply(ReplyRequest $request, \App\Actions\Conversations\ReplyToTicketAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['ticket_id' => $ticket]))]);
    }

    public function note(NoteRequest $request, \App\Actions\Conversations\AddNoteAction $action, int $ticket): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['ticket_id' => $ticket]))]);
    }

    public function update(UpdateRequest $request, \App\Actions\Conversations\UpdateConversationAction $action, int $conversation): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $conversation]))]);
    }

    public function destroy(Request $request, \App\Actions\Conversations\DeleteConversationAction $action, int $conversation): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $conversation]))]);
    }
}
