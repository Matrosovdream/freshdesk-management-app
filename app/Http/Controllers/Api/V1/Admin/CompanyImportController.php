<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Companies\ImportRequest;
use Illuminate\Http\JsonResponse;

class CompanyImportController extends Controller
{
    public function store(ImportRequest $request, \App\Actions\Companies\ImportCompaniesAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }
}
