<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Contacts\ImportRequest;
use Illuminate\Http\JsonResponse;

class ContactImportController extends Controller
{
    public function store(ImportRequest $request, \App\Actions\Contacts\ImportContactsAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
