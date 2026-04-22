<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyExportController extends Controller
{
    public function store(Request $request, \App\Actions\Companies\ExportCompaniesAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }
}
