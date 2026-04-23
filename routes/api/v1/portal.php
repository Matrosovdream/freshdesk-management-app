<?php

use App\Http\Controllers\Api\V1\Portal;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal API — /api/v1/portal/*
|--------------------------------------------------------------------------
*/

// --- Public config (unauthenticated) -----------------------------------------
Route::get('/config/public', [Portal\Config\PublicConfigController::class, 'index'])->name('config.public');

// --- Auth (public) -----------------------------------------------------------
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login',              [Portal\Auth\SessionController::class, 'store'])->name('login');
    Route::post('/logout',             [Portal\Auth\SessionController::class, 'destroy'])->name('logout');
    Route::post('/register',           [Portal\Auth\RegisterController::class, 'store'])->name('register');
    Route::post('/magic-link',         [Portal\Auth\MagicLinkController::class, 'send'])->name('magic-link.send');
    Route::post('/magic-link/consume', [Portal\Auth\MagicLinkController::class, 'consume'])->name('magic-link.consume');
    Route::post('/verify',             [Portal\Auth\VerifyEmailController::class, 'store'])->name('verify');
    Route::post('/forgot',             [Portal\Auth\PasswordResetController::class, 'sendLink'])->name('forgot');
    Route::post('/reset',              [Portal\Auth\PasswordResetController::class, 'reset'])->name('reset');
});

// --- Authenticated (any role) — identity endpoints used by the unified login -
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/auth/me',               [Portal\Auth\MeController::class, 'show'])->name('auth.me');
    Route::post('/auth/logout-others',   [Portal\Auth\SessionController::class, 'logoutOthers'])->name('auth.logout-others');
});

// --- Authenticated (customer) ------------------------------------------------
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::get('/home',                        [Portal\HomeController::class, 'index']);

    Route::get('/requests',                    [Portal\RequestController::class, 'index'])->middleware('right:portal.requests.view_own');
    Route::post('/requests',                   [Portal\RequestController::class, 'store'])->middleware('right:portal.requests.create');
    Route::get('/requests/{id}',               [Portal\RequestController::class, 'show'])->middleware('right:portal.requests.view_own');
    Route::post('/requests/{id}/reply',        [Portal\RequestController::class, 'reply'])->middleware('right:portal.requests.reply');
    Route::post('/requests/{id}/resolve',      [Portal\RequestController::class, 'resolve'])->middleware('right:portal.requests.resolve');
    Route::post('/requests/{id}/reopen',       [Portal\RequestController::class, 'reopen'])->middleware('right:portal.requests.reopen');
    Route::post('/requests/{id}/rate',         [Portal\RequestController::class, 'rate'])->middleware('right:portal.requests.rate');

    Route::get('/drafts',                      [Portal\DraftController::class, 'show']);
    Route::post('/drafts',                     [Portal\DraftController::class, 'save'])->middleware('right:portal.requests.create');
    Route::delete('/drafts',                   [Portal\DraftController::class, 'clear']);

    Route::get('/profile',                     [Portal\ProfileController::class, 'show']);
    Route::put('/profile',                     [Portal\ProfileController::class, 'update'])->middleware('right:portal.profile.update');
    Route::put('/profile/password',            [Portal\ProfileController::class, 'changePassword']);
    Route::delete('/profile',                  [Portal\ProfileController::class, 'destroy']);

    Route::get('/ticket-fields',               [Portal\Config\TicketFieldController::class, 'index']);
    Route::get('/products',                    [Portal\Config\ProductController::class, 'index']);
});
