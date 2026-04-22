<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoriesProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            // User
            \App\Repositories\User\UserRepo::class,
            \App\Repositories\User\RoleRepo::class,
            \App\Repositories\User\RoleRightRepo::class,
            // People
            \App\Repositories\People\ContactRepo::class,
            \App\Repositories\People\CompanyRepo::class,
            \App\Repositories\People\AgentRepo::class,
            // Ticket
            \App\Repositories\Ticket\TicketRepo::class,
            \App\Repositories\Ticket\ConversationRepo::class,
            \App\Repositories\Ticket\TimeEntryRepo::class,
            // Group
            \App\Repositories\Group\GroupRepo::class,
            \App\Repositories\Group\ManagerGroupScopeRepo::class,
            // System
            \App\Repositories\System\SettingRepo::class,
            \App\Repositories\System\SyncJobRepo::class,
            \App\Repositories\System\AuditLogRepo::class,
            \App\Repositories\System\ApiKeyRepo::class,
            // Portal
            \App\Repositories\Portal\PortalDraftRepo::class,
        ] as $repo) {
            $this->app->singleton($repo);
        }
    }
}
