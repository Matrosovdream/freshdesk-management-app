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
Route::middleware(['auth:sanctum'])->prefix('auth')->name('auth.')->group(function () {
    Route::get('/me',             [Portal\Auth\MeController::class, 'show'])->name('me');
    Route::post('/logout-others', [Portal\Auth\SessionController::class, 'logoutOthers'])->name('logout-others');
});

// --- Authenticated (customer) ------------------------------------------------
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::get('/home', [Portal\HomeController::class, 'index'])->name('home.index');

    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/',               [Portal\RequestController::class, 'index'])->middleware('right:portal.requests.view_own')->name('index');
        Route::post('/',              [Portal\RequestController::class, 'store'])->middleware('right:portal.requests.create')->name('store');
        Route::get('/{id}',           [Portal\RequestController::class, 'show'])->middleware('right:portal.requests.view_own')->name('show');
        Route::post('/{id}/reply',    [Portal\RequestController::class, 'reply'])->middleware('right:portal.requests.reply')->name('reply');
        Route::post('/{id}/resolve',  [Portal\RequestController::class, 'resolve'])->middleware('right:portal.requests.resolve')->name('resolve');
        Route::post('/{id}/reopen',   [Portal\RequestController::class, 'reopen'])->middleware('right:portal.requests.reopen')->name('reopen');
        Route::post('/{id}/rate',     [Portal\RequestController::class, 'rate'])->middleware('right:portal.requests.rate')->name('rate');
    });

    Route::prefix('drafts')->name('drafts.')->group(function () {
        Route::get('/',    [Portal\DraftController::class, 'show'])->name('show');
        Route::post('/',   [Portal\DraftController::class, 'save'])->middleware('right:portal.requests.create')->name('save');
        Route::delete('/', [Portal\DraftController::class, 'clear'])->name('clear');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',         [Portal\ProfileController::class, 'show'])->name('show');
        Route::put('/',         [Portal\ProfileController::class, 'update'])->middleware('right:portal.profile.update')->name('update');
        Route::put('/password', [Portal\ProfileController::class, 'changePassword'])->name('password');
        Route::delete('/',      [Portal\ProfileController::class, 'destroy'])->name('destroy');
    });

    Route::get('/ticket-fields', [Portal\Config\TicketFieldController::class, 'index'])->name('ticket_fields.index');
    Route::get('/products',      [Portal\Config\ProductController::class, 'index'])->name('products.index');
});
