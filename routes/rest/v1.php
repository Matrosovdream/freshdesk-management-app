<?php

use App\Http\Controllers\Rest\V1\HealthController;
use App\Http\Controllers\Rest\V1\Webhooks\FreshdeskController;
use App\Http\Controllers\Rest\V1\Webhooks\PingController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/freshdesk',                 [FreshdeskController::class, 'handle'])->name('rest.webhooks.freshdesk');
Route::post('/webhooks/freshdesk/ticket-created',  [FreshdeskController::class, 'ticketCreated']);
Route::post('/webhooks/freshdesk/ticket-updated',  [FreshdeskController::class, 'ticketUpdated']);
Route::post('/webhooks/freshdesk/ticket-replied',  [FreshdeskController::class, 'ticketReplied']);
Route::post('/webhooks/freshdesk/contact-updated', [FreshdeskController::class, 'contactUpdated']);

Route::get('/webhooks/ping', [PingController::class, '__invoke']);

Route::get('/health', [HealthController::class, '__invoke'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyWebhookSignature::class]);
