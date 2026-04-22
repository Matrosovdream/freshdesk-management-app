<?php

use App\Http\Controllers\Web\DownloadController;
use App\Http\Controllers\Web\SpaShellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SpaShellController::class, 'root'])->name('spa.root');

Route::get('/dashboard/{any?}', [SpaShellController::class, 'dashboard'])
    ->where('any', '.*')
    ->name('spa.dashboard');

Route::get('/portal/{any?}', [SpaShellController::class, 'portal'])
    ->where('any', '.*')
    ->name('spa.portal');

Route::get('/downloads/{signed}', [DownloadController::class, 'show'])
    ->middleware('signed')
    ->name('downloads.show');
