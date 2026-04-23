<?php

use App\Http\Controllers\Api\V1\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API — /api/v1/admin/*
|--------------------------------------------------------------------------
*/

// --- Auth (public) -----------------------------------------------------------
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login',  [Admin\Auth\SessionController::class, 'store'])->name('login');
    Route::post('/logout', [Admin\Auth\SessionController::class, 'destroy'])->name('logout');
    Route::post('/forgot', [Admin\Auth\PasswordResetController::class, 'sendLink'])->name('forgot');
    Route::post('/reset',  [Admin\Auth\PasswordResetController::class, 'reset'])->name('reset');
});

// --- Authenticated (superadmin | manager) ------------------------------------
Route::middleware(['auth:sanctum', 'role:superadmin|manager', 'manager.scope'])->group(function () {
    Route::get('/auth/me', [Admin\Auth\MeController::class, 'show'])->name('auth.me');
    Route::post('/auth/logout-others', [Admin\ProfileController::class, 'logoutOthers'])->name('auth.logout_others');

    Route::get('/profile', [Admin\ProfileController::class, 'show']);
    Route::put('/profile', [Admin\ProfileController::class, 'update']);

    // Overview
    Route::get('/overview',          [Admin\OverviewController::class, 'index']);
    Route::post('/overview/refresh', [Admin\OverviewController::class, 'refresh'])->middleware('right:system.sync_jobs.run');

    // Tickets
    Route::middleware('manager.scope')->group(function () {
        Route::get('/tickets',                        [Admin\TicketController::class, 'index'])->middleware('right:tickets.view');
        Route::post('/tickets',                       [Admin\TicketController::class, 'store'])->middleware('right:tickets.create');
        Route::get('/tickets/{id}',                   [Admin\TicketController::class, 'show'])->middleware('right:tickets.view');
        Route::put('/tickets/{id}',                   [Admin\TicketController::class, 'update'])->middleware('right:tickets.update');
        Route::delete('/tickets/{id}',                [Admin\TicketController::class, 'destroy'])->middleware('right:tickets.delete');
        Route::post('/tickets/{id}/restore',          [Admin\TicketController::class, 'restore'])->middleware('right:tickets.restore');
        Route::post('/tickets/bulk-update',           [Admin\TicketBulkController::class, 'update'])->middleware('right:tickets.bulk_update');
        Route::post('/tickets/bulk-delete',           [Admin\TicketBulkController::class, 'destroy'])->middleware('right:tickets.bulk_delete');
        Route::post('/tickets/merge',                 [Admin\TicketController::class, 'merge'])->middleware('right:tickets.merge');
        Route::post('/tickets/{id}/forward',          [Admin\TicketController::class, 'forward'])->middleware('right:tickets.forward');
        Route::post('/tickets/outbound-email',        [Admin\TicketController::class, 'outboundEmail'])->middleware('right:tickets.outbound_email');
        Route::post('/tickets/{id}/assign',           [Admin\TicketController::class, 'assign'])->middleware('right:tickets.assign');

        // Conversations
        Route::get('/tickets/{id}/conversations',     [Admin\ConversationController::class, 'index'])->middleware('right:tickets.view');
        Route::post('/tickets/{id}/reply',            [Admin\ConversationController::class, 'reply'])->middleware('right:conversations.reply');
        Route::post('/tickets/{id}/note',             [Admin\ConversationController::class, 'note'])->middleware('right:conversations.note');
        Route::put('/conversations/{id}',             [Admin\ConversationController::class, 'update'])->middleware('right:conversations.update');
        Route::delete('/conversations/{id}',          [Admin\ConversationController::class, 'destroy'])->middleware('right:conversations.delete');

        // Time entries
        Route::get('/tickets/{id}/time-entries',      [Admin\TimeEntryController::class, 'index'])->middleware('right:time_entries.view');
        Route::post('/tickets/{id}/time-entries',     [Admin\TimeEntryController::class, 'store'])->middleware('right:time_entries.create');
        Route::put('/time-entries/{id}',              [Admin\TimeEntryController::class, 'update'])->middleware('right:time_entries.update');
        Route::delete('/time-entries/{id}',           [Admin\TimeEntryController::class, 'destroy'])->middleware('right:time_entries.delete');
    });

    // Contacts
    Route::get('/contacts',                      [Admin\ContactController::class, 'index'])->middleware('right:contacts.view');
    Route::post('/contacts',                     [Admin\ContactController::class, 'store'])->middleware('right:contacts.create');
    Route::get('/contacts/{id}',                 [Admin\ContactController::class, 'show'])->middleware('right:contacts.view');
    Route::put('/contacts/{id}',                 [Admin\ContactController::class, 'update'])->middleware('right:contacts.update');
    Route::delete('/contacts/{id}',              [Admin\ContactController::class, 'destroy'])->middleware('right:contacts.delete');
    Route::post('/contacts/{id}/hard-delete',    [Admin\ContactController::class, 'hardDestroy'])->middleware('right:contacts.hard_delete');
    Route::post('/contacts/{id}/restore',        [Admin\ContactController::class, 'restore'])->middleware('right:contacts.restore');
    Route::post('/contacts/{id}/send-invite',    [Admin\ContactController::class, 'sendInvite'])->middleware('right:contacts.send_invite');
    Route::post('/contacts/{id}/make-agent',     [Admin\ContactController::class, 'makeAgent'])->middleware('right:contacts.make_agent');
    Route::post('/contacts/merge',               [Admin\ContactController::class, 'merge'])->middleware('right:contacts.merge');
    Route::post('/contacts/import',              [Admin\ContactImportController::class, 'store'])->middleware('right:contacts.import');
    Route::post('/contacts/export',              [Admin\ContactExportController::class, 'store'])->middleware('right:contacts.export');

    // Companies
    Route::get('/companies',                     [Admin\CompanyController::class, 'index'])->middleware('right:companies.view');
    Route::post('/companies',                    [Admin\CompanyController::class, 'store'])->middleware('right:companies.create');
    Route::get('/companies/{id}',                [Admin\CompanyController::class, 'show'])->middleware('right:companies.view');
    Route::put('/companies/{id}',                [Admin\CompanyController::class, 'update'])->middleware('right:companies.update');
    Route::delete('/companies/{id}',             [Admin\CompanyController::class, 'destroy'])->middleware('right:companies.delete');
    Route::post('/companies/import',             [Admin\CompanyImportController::class, 'store'])->middleware('right:companies.import');
    Route::post('/companies/export',             [Admin\CompanyExportController::class, 'store'])->middleware('right:companies.export');

    // Agents
    Route::get('/agents',                        [Admin\AgentController::class, 'index'])->middleware('right:agents.view');
    Route::post('/agents',                       [Admin\AgentController::class, 'store'])->middleware('right:agents.create');
    Route::get('/agents/{id}',                   [Admin\AgentController::class, 'show'])->middleware('right:agents.view');
    Route::put('/agents/{id}',                   [Admin\AgentController::class, 'update'])->middleware('right:agents.update');
    Route::delete('/agents/{id}',                [Admin\AgentController::class, 'destroy'])->middleware('right:agents.delete');
    Route::post('/agents/bulk',                  [Admin\AgentController::class, 'bulkCreate'])->middleware('right:agents.bulk_create');

    // Groups
    Route::get('/groups',                        [Admin\GroupController::class, 'index'])->middleware('right:groups.view');
    Route::post('/groups',                       [Admin\GroupController::class, 'store'])->middleware('right:groups.create');
    Route::put('/groups/{id}',                   [Admin\GroupController::class, 'update'])->middleware('right:groups.update');
    Route::delete('/groups/{id}',                [Admin\GroupController::class, 'destroy'])->middleware('right:groups.delete');

    // Reports
    Route::prefix('reports')->middleware('right:reports.view')->group(function () {
        Route::get('/backlog',           [Admin\Reports\BacklogController::class, '__invoke']);
        Route::get('/agent-performance', [Admin\Reports\AgentPerformanceController::class, '__invoke']);
        Route::get('/group-performance', [Admin\Reports\GroupPerformanceController::class, '__invoke']);
        Route::get('/sla-breaches',      [Admin\Reports\SlaBreachController::class, '__invoke']);
        Route::get('/volume',            [Admin\Reports\VolumeController::class, '__invoke']);
        Route::get('/csat',              [Admin\Reports\CsatController::class, '__invoke']);
    });
    Route::post('/reports/{report}/export', [Admin\Reports\ExportController::class, 'store'])->middleware('right:reports.export');

    // Audit log
    Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->middleware('right:audit.view');

    // Config lookups
    Route::get('/ticket-fields',  [Admin\Config\TicketFieldController::class, 'index'])->middleware('right:tickets.view');
    Route::get('/products',       [Admin\Config\ProductController::class, 'index'])->middleware('right:tickets.view');
    Route::get('/business-hours', [Admin\Config\BusinessHoursController::class, 'index'])->middleware('right:tickets.view');
    Route::get('/sla-policies',   [Admin\Config\SlaPolicyController::class, 'index'])->middleware('right:reports.view');
    Route::get('/automations',    [Admin\Config\AutomationController::class, 'index'])->middleware('right:reports.view');
    Route::get('/roles',          [Admin\Config\RoleController::class, 'index']);

    // System (superadmin only)
    Route::middleware('role:superadmin')->prefix('system')->group(function () {
        Route::get('/freshdesk',              [Admin\System\FreshdeskConnectionController::class, 'show'])->middleware('right:system.freshdesk.view');
        Route::put('/freshdesk',              [Admin\System\FreshdeskConnectionController::class, 'update'])->middleware('right:system.freshdesk.update');
        Route::post('/freshdesk/test',        [Admin\System\FreshdeskConnectionController::class, 'test'])->middleware('right:system.freshdesk.view');

        Route::get('/managers',               [Admin\System\ManagerController::class, 'index'])->middleware('right:system.managers.view');
        Route::post('/managers',              [Admin\System\ManagerController::class, 'store'])->middleware('right:system.managers.create');
        Route::get('/managers/{id}',          [Admin\System\ManagerController::class, 'show'])->middleware('right:system.managers.view');
        Route::put('/managers/{id}',          [Admin\System\ManagerController::class, 'update'])->middleware('right:system.managers.update');
        Route::delete('/managers/{id}',       [Admin\System\ManagerController::class, 'destroy'])->middleware('right:system.managers.delete');
        Route::post('/managers/{id}/scope',   [Admin\System\ManagerController::class, 'setScope'])->middleware('right:system.managers.update');

        Route::get('/sync-jobs',                        [Admin\System\SyncJobController::class, 'index'])->middleware('right:system.sync_jobs.view');
        Route::post('/sync-jobs/{resource}/run',        [Admin\System\SyncJobController::class, 'run'])->middleware('right:system.sync_jobs.run');
        Route::post('/sync-jobs/full-resync',           [Admin\System\SyncJobController::class, 'fullResync'])->middleware('right:system.sync_jobs.run');

        Route::get('/settings',               [Admin\System\SettingsController::class, 'index'])->middleware('right:system.settings.view');
        Route::put('/settings',               [Admin\System\SettingsController::class, 'update'])->middleware('right:system.settings.update');

        Route::get('/api-keys',               [Admin\System\ApiKeyController::class, 'index'])->middleware('right:system.api_keys.view');
        Route::post('/api-keys',              [Admin\System\ApiKeyController::class, 'store'])->middleware('right:system.api_keys.create');
        Route::post('/api-keys/{id}/rotate',  [Admin\System\ApiKeyController::class, 'rotate'])->middleware('right:system.api_keys.rotate');
        Route::post('/api-keys/{id}/revoke',  [Admin\System\ApiKeyController::class, 'revoke'])->middleware('right:system.api_keys.revoke');
    });
});
