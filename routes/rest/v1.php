<?php

use App\Http\Controllers\Rest\V1\HealthController;
use App\Http\Controllers\Rest\V1\Webhooks\FreshdeskController;
use App\Http\Controllers\Rest\V1\Webhooks\PingController;
use Illuminate\Support\Facades\Route;

Route::name('rest.')->group(function () {
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::prefix('freshdesk')->name('freshdesk.')->group(function () {
            Route::post('/',                 [FreshdeskController::class, 'handle'])->name('handle');
            Route::post('/ticket-created',   [FreshdeskController::class, 'ticketCreated'])->name('ticket_created');
            Route::post('/ticket-updated',   [FreshdeskController::class, 'ticketUpdated'])->name('ticket_updated');
            Route::post('/ticket-replied',   [FreshdeskController::class, 'ticketReplied'])->name('ticket_replied');
            Route::post('/contact-updated', [FreshdeskController::class, 'contactUpdated'])->name('contact_updated');
        });

        Route::get('/ping', [PingController::class, '__invoke'])->name('ping');
    });

    Route::get('/health', [HealthController::class, '__invoke'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyWebhookSignature::class])
        ->name('health');
});
