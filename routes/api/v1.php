<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 routes — prefix /api/v1
|--------------------------------------------------------------------------
| Two namespaces: admin (/api/v1/admin/*) + portal (/api/v1/portal/*).
| Sanctum SPA cookie auth OR Bearer token auth (same guard, both accepted).
*/

Route::prefix('admin')->name('api.admin.')->group(base_path('routes/api/v1/admin.php'));
Route::prefix('portal')->name('api.portal.')->group(base_path('routes/api/v1/portal.php'));
