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
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me',              [Admin\Auth\MeController::class, 'show'])->name('me');
        Route::post('/logout-others',  [Admin\ProfileController::class, 'logoutOthers'])->name('logout_others');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [Admin\ProfileController::class, 'show'])->name('show');
        Route::put('/', [Admin\ProfileController::class, 'update'])->name('update');
    });

    Route::prefix('overview')->name('overview.')->group(function () {
        Route::get('/',        [Admin\OverviewController::class, 'index'])->name('index');
        Route::post('/refresh', [Admin\OverviewController::class, 'refresh'])->middleware('right:system.sync_jobs.run')->name('refresh');
    });

    // Tickets (incl. conversations + time entries under manager.scope)
    Route::middleware('manager.scope')->group(function () {
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                  [Admin\TicketController::class, 'index'])->middleware('right:tickets.view')->name('index');
            Route::post('/',                 [Admin\TicketController::class, 'store'])->middleware('right:tickets.create')->name('store');
            Route::get('/{id}',              [Admin\TicketController::class, 'show'])->middleware('right:tickets.view')->name('show');
            Route::put('/{id}',              [Admin\TicketController::class, 'update'])->middleware('right:tickets.update')->name('update');
            Route::delete('/{id}',           [Admin\TicketController::class, 'destroy'])->middleware('right:tickets.delete')->name('destroy');
            Route::post('/{id}/restore',     [Admin\TicketController::class, 'restore'])->middleware('right:tickets.restore')->name('restore');
            Route::post('/bulk-update',      [Admin\TicketBulkController::class, 'update'])->middleware('right:tickets.bulk_update')->name('bulk_update');
            Route::post('/bulk-delete',      [Admin\TicketBulkController::class, 'destroy'])->middleware('right:tickets.bulk_delete')->name('bulk_delete');
            Route::post('/merge',            [Admin\TicketController::class, 'merge'])->middleware('right:tickets.merge')->name('merge');
            Route::post('/{id}/forward',     [Admin\TicketController::class, 'forward'])->middleware('right:tickets.forward')->name('forward');
            Route::post('/outbound-email',   [Admin\TicketController::class, 'outboundEmail'])->middleware('right:tickets.outbound_email')->name('outbound_email');
            Route::post('/{id}/assign',      [Admin\TicketController::class, 'assign'])->middleware('right:tickets.assign')->name('assign');
            Route::get('/{id}/activity',     [Admin\TicketController::class, 'activity'])->middleware('right:tickets.view')->name('activity');

            // Conversations nested under ticket {id}
            Route::get('/{id}/conversations', [Admin\ConversationController::class, 'index'])->middleware('right:tickets.view')->name('conversations.index');
            Route::post('/{id}/reply',        [Admin\ConversationController::class, 'reply'])->middleware('right:conversations.reply')->name('reply');
            Route::post('/{id}/note',         [Admin\ConversationController::class, 'note'])->middleware('right:conversations.note')->name('note');

            // Time entries nested under ticket {id}
            Route::get('/{id}/time-entries',  [Admin\TimeEntryController::class, 'index'])->middleware('right:time_entries.view')->name('time_entries.index');
            Route::post('/{id}/time-entries', [Admin\TimeEntryController::class, 'store'])->middleware('right:time_entries.create')->name('time_entries.store');
        });

        Route::prefix('conversations')->name('conversations.')->group(function () {
            Route::put('/{id}',    [Admin\ConversationController::class, 'update'])->middleware('right:conversations.update')->name('update');
            Route::delete('/{id}', [Admin\ConversationController::class, 'destroy'])->middleware('right:conversations.delete')->name('destroy');
        });

        Route::prefix('time-entries')->name('time_entries.')->group(function () {
            Route::put('/{id}',    [Admin\TimeEntryController::class, 'update'])->middleware('right:time_entries.update')->name('update');
            Route::delete('/{id}', [Admin\TimeEntryController::class, 'destroy'])->middleware('right:time_entries.delete')->name('destroy');
        });
    });

    // Contacts
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/',                     [Admin\ContactController::class, 'index'])->middleware('right:contacts.view')->name('index');
        Route::post('/',                    [Admin\ContactController::class, 'store'])->middleware('right:contacts.create')->name('store');
        Route::get('/{id}',                 [Admin\ContactController::class, 'show'])->middleware('right:contacts.view')->name('show');
        Route::put('/{id}',                 [Admin\ContactController::class, 'update'])->middleware('right:contacts.update')->name('update');
        Route::delete('/{id}',              [Admin\ContactController::class, 'destroy'])->middleware('right:contacts.delete')->name('destroy');
        Route::post('/{id}/hard-delete',    [Admin\ContactController::class, 'hardDestroy'])->middleware('right:contacts.hard_delete')->name('hard_delete');
        Route::post('/{id}/restore',        [Admin\ContactController::class, 'restore'])->middleware('right:contacts.restore')->name('restore');
        Route::post('/{id}/send-invite',    [Admin\ContactController::class, 'sendInvite'])->middleware('right:contacts.send_invite')->name('send_invite');
        Route::post('/{id}/make-agent',     [Admin\ContactController::class, 'makeAgent'])->middleware('right:contacts.make_agent')->name('make_agent');
        Route::post('/merge',               [Admin\ContactController::class, 'merge'])->middleware('right:contacts.merge')->name('merge');
        Route::post('/import',              [Admin\ContactImportController::class, 'store'])->middleware('right:contacts.import')->name('import');
        Route::post('/export',              [Admin\ContactExportController::class, 'store'])->middleware('right:contacts.export')->name('export');
    });

    // Companies
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/',         [Admin\CompanyController::class, 'index'])->middleware('right:companies.view')->name('index');
        Route::post('/',        [Admin\CompanyController::class, 'store'])->middleware('right:companies.create')->name('store');
        Route::get('/{id}',     [Admin\CompanyController::class, 'show'])->middleware('right:companies.view')->name('show');
        Route::put('/{id}',     [Admin\CompanyController::class, 'update'])->middleware('right:companies.update')->name('update');
        Route::delete('/{id}',  [Admin\CompanyController::class, 'destroy'])->middleware('right:companies.delete')->name('destroy');
        Route::post('/import',  [Admin\CompanyImportController::class, 'store'])->middleware('right:companies.import')->name('import');
        Route::post('/export',  [Admin\CompanyExportController::class, 'store'])->middleware('right:companies.export')->name('export');
    });

    // Agents
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/',         [Admin\AgentController::class, 'index'])->middleware('right:agents.view')->name('index');
        Route::post('/',        [Admin\AgentController::class, 'store'])->middleware('right:agents.create')->name('store');
        Route::get('/{id}',     [Admin\AgentController::class, 'show'])->middleware('right:agents.view')->name('show');
        Route::put('/{id}',     [Admin\AgentController::class, 'update'])->middleware('right:agents.update')->name('update');
        Route::delete('/{id}',  [Admin\AgentController::class, 'destroy'])->middleware('right:agents.delete')->name('destroy');
        Route::post('/bulk',    [Admin\AgentController::class, 'bulkCreate'])->middleware('right:agents.bulk_create')->name('bulk_create');
    });

    // Groups
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/',         [Admin\GroupController::class, 'index'])->middleware('right:groups.view')->name('index');
        Route::post('/',        [Admin\GroupController::class, 'store'])->middleware('right:groups.create')->name('store');
        Route::put('/{id}',     [Admin\GroupController::class, 'update'])->middleware('right:groups.update')->name('update');
        Route::delete('/{id}',  [Admin\GroupController::class, 'destroy'])->middleware('right:groups.delete')->name('destroy');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::middleware('right:reports.view')->group(function () {
            Route::get('/backlog',           [Admin\Reports\BacklogController::class, '__invoke'])->name('backlog');
            Route::get('/agent-performance', [Admin\Reports\AgentPerformanceController::class, '__invoke'])->name('agent_performance');
            Route::get('/group-performance', [Admin\Reports\GroupPerformanceController::class, '__invoke'])->name('group_performance');
            Route::get('/sla-breaches',      [Admin\Reports\SlaBreachController::class, '__invoke'])->name('sla_breaches');
            Route::get('/volume',            [Admin\Reports\VolumeController::class, '__invoke'])->name('volume');
            Route::get('/csat',              [Admin\Reports\CsatController::class, '__invoke'])->name('csat');
        });
        Route::post('/{report}/export', [Admin\Reports\ExportController::class, 'store'])->middleware('right:reports.export')->name('export');
    });

    // Audit log
    Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->middleware('right:audit.view')->name('audit_log.index');

    // Config lookups
    Route::get('/ticket-fields',  [Admin\Config\TicketFieldController::class, 'index'])->middleware('right:tickets.view')->name('ticket_fields.index');
    Route::get('/products',       [Admin\Config\ProductController::class, 'index'])->middleware('right:tickets.view')->name('products.index');
    Route::get('/business-hours', [Admin\Config\BusinessHoursController::class, 'index'])->middleware('right:tickets.view')->name('business_hours.index');
    Route::get('/sla-policies',   [Admin\Config\SlaPolicyController::class, 'index'])->middleware('right:reports.view')->name('sla_policies.index');
    Route::get('/automations',    [Admin\Config\AutomationController::class, 'index'])->middleware('right:reports.view')->name('automations.index');
    Route::get('/roles',          [Admin\Config\RoleController::class, 'index'])->name('roles.index');

    // System (superadmin only)
    Route::middleware('role:superadmin')->prefix('system')->name('system.')->group(function () {
        Route::prefix('freshdesk')->name('freshdesk.')->group(function () {
            Route::get('/',     [Admin\System\FreshdeskConnectionController::class, 'show'])->middleware('right:system.freshdesk.view')->name('show');
            Route::put('/',     [Admin\System\FreshdeskConnectionController::class, 'update'])->middleware('right:system.freshdesk.update')->name('update');
            Route::post('/test', [Admin\System\FreshdeskConnectionController::class, 'test'])->middleware('right:system.freshdesk.view')->name('test');
        });

        Route::prefix('managers')->name('managers.')->group(function () {
            Route::get('/',             [Admin\System\ManagerController::class, 'index'])->middleware('right:system.managers.view')->name('index');
            Route::post('/',            [Admin\System\ManagerController::class, 'store'])->middleware('right:system.managers.create')->name('store');
            Route::get('/{id}',         [Admin\System\ManagerController::class, 'show'])->middleware('right:system.managers.view')->name('show');
            Route::put('/{id}',         [Admin\System\ManagerController::class, 'update'])->middleware('right:system.managers.update')->name('update');
            Route::delete('/{id}',      [Admin\System\ManagerController::class, 'destroy'])->middleware('right:system.managers.delete')->name('destroy');
            Route::post('/{id}/scope',  [Admin\System\ManagerController::class, 'setScope'])->middleware('right:system.managers.update')->name('scope');
        });

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',         [Admin\System\UserController::class, 'index'])->middleware('right:system.users.view')->name('index');
            Route::post('/',        [Admin\System\UserController::class, 'store'])->middleware('right:system.users.create')->name('store');
            Route::get('/{user}',   [Admin\System\UserController::class, 'show'])->middleware('right:system.users.view')->name('show');
            Route::put('/{user}',   [Admin\System\UserController::class, 'update'])->middleware('right:system.users.update')->name('update');
            Route::delete('/{user}',[Admin\System\UserController::class, 'destroy'])->middleware('right:system.users.delete')->name('destroy');
        });

        Route::prefix('sync-jobs')->name('sync_jobs.')->group(function () {
            Route::get('/',                  [Admin\System\SyncJobController::class, 'index'])->middleware('right:system.sync_jobs.view')->name('index');
            Route::post('/{resource}/run',   [Admin\System\SyncJobController::class, 'run'])->middleware('right:system.sync_jobs.run')->name('run');
            Route::post('/full-resync',      [Admin\System\SyncJobController::class, 'fullResync'])->middleware('right:system.sync_jobs.run')->name('full_resync');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [Admin\System\SettingsController::class, 'index'])->middleware('right:system.settings.view')->name('index');
            Route::put('/', [Admin\System\SettingsController::class, 'update'])->middleware('right:system.settings.update')->name('update');
        });

        Route::prefix('api-keys')->name('api_keys.')->group(function () {
            Route::get('/',              [Admin\System\ApiKeyController::class, 'index'])->middleware('right:system.api_keys.view')->name('index');
            Route::post('/',             [Admin\System\ApiKeyController::class, 'store'])->middleware('right:system.api_keys.create')->name('store');
            Route::post('/{id}/rotate',  [Admin\System\ApiKeyController::class, 'rotate'])->middleware('right:system.api_keys.rotate')->name('rotate');
            Route::post('/{id}/revoke',  [Admin\System\ApiKeyController::class, 'revoke'])->middleware('right:system.api_keys.revoke')->name('revoke');
        });
    });
});
