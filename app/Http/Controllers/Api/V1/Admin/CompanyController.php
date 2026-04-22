<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Companies\StoreRequest;
use App\Http\Requests\Api\V1\Admin\Companies\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request, \App\Actions\Companies\ListCompaniesAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->all())]);
    }

    public function store(StoreRequest $request, \App\Actions\Companies\CreateCompanyAction $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->validated())]);
    }

    public function show(Request $request, \App\Actions\Companies\GetCompanyAction $action, int $company): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $company]))]);
    }

    public function update(UpdateRequest $request, \App\Actions\Companies\UpdateCompanyAction $action, int $company): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->validated(), ['id' => $company]))]);
    }

    public function destroy(Request $request, \App\Actions\Companies\DeleteCompanyAction $action, int $company): JsonResponse
    {
        return response()->json(['data' => $action->handle(array_merge($request->all(), ['id' => $company]))]);
    }
}
