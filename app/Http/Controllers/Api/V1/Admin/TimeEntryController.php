<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\TimeEntries\StoreRequest;
use App\Http\Requests\Api\V1\Admin\TimeEntries\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function index(Request $request, \App\Actions\TimeEntries\ListTimeEntriesAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\TimeEntries\CreateTimeEntryAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function update(UpdateRequest $request, \App\Actions\TimeEntries\UpdateTimeEntryAction $action, int $timeEntry): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $timeEntry]))]);
    }

    public function destroy(Request $request, \App\Actions\TimeEntries\DeleteTimeEntryAction $action, int $timeEntry): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $timeEntry]))]);
    }
}
