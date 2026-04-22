<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Contacts\MergeRequest;
use App\Http\Requests\Api\V1\Admin\Contacts\StoreRequest;
use App\Http\Requests\Api\V1\Admin\Contacts\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request, \App\Actions\Contacts\ListContactsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\Contacts\CreateContactAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function show(Request $request, \App\Actions\Contacts\GetContactAction $action, int $contact): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $contact]))]);
    }

    public function update(UpdateRequest $request, \App\Actions\Contacts\UpdateContactAction $action, int $contact): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $contact]))]);
    }

    public function destroy(Request $request, \App\Actions\Contacts\DeleteContactAction $action, int $contact): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $contact]))]);
    }

    public function hardDestroy(Request $request, \App\Actions\Contacts\HardDeleteContactAction $action, int $contact): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $contact]))]);
    }

    public function restore(Request $request, \App\Actions\Contacts\RestoreContactAction $action, int $contact): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $contact]))]);
    }

    public function sendInvite(Request $request, \App\Actions\Contacts\SendInviteAction $action, int $contact): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $contact]))]);
    }

    public function makeAgent(Request $request, \App\Actions\Contacts\MakeAgentAction $action, int $contact): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $contact]))]);
    }

    public function merge(MergeRequest $request, \App\Actions\Contacts\MergeContactsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
