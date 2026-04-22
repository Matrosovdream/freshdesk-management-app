<?php

namespace App\Support;

final class Rights
{
    // ---- Tickets ----
    public const TICKETS_VIEW           = 'tickets.view';
    public const TICKETS_CREATE         = 'tickets.create';
    public const TICKETS_UPDATE         = 'tickets.update';
    public const TICKETS_DELETE         = 'tickets.delete';
    public const TICKETS_HARD_DELETE    = 'tickets.hard_delete';
    public const TICKETS_RESTORE        = 'tickets.restore';
    public const TICKETS_BULK_UPDATE    = 'tickets.bulk_update';
    public const TICKETS_BULK_DELETE    = 'tickets.bulk_delete';
    public const TICKETS_MERGE          = 'tickets.merge';
    public const TICKETS_FORWARD        = 'tickets.forward';
    public const TICKETS_OUTBOUND_EMAIL = 'tickets.outbound_email';
    public const TICKETS_ASSIGN         = 'tickets.assign';

    // ---- Conversations ----
    public const CONVERSATIONS_REPLY  = 'conversations.reply';
    public const CONVERSATIONS_NOTE   = 'conversations.note';
    public const CONVERSATIONS_UPDATE = 'conversations.update';
    public const CONVERSATIONS_DELETE = 'conversations.delete';

    // ---- Contacts ----
    public const CONTACTS_VIEW        = 'contacts.view';
    public const CONTACTS_CREATE      = 'contacts.create';
    public const CONTACTS_UPDATE      = 'contacts.update';
    public const CONTACTS_DELETE      = 'contacts.delete';
    public const CONTACTS_HARD_DELETE = 'contacts.hard_delete';
    public const CONTACTS_RESTORE     = 'contacts.restore';
    public const CONTACTS_MERGE       = 'contacts.merge';
    public const CONTACTS_SEND_INVITE = 'contacts.send_invite';
    public const CONTACTS_MAKE_AGENT  = 'contacts.make_agent';
    public const CONTACTS_IMPORT      = 'contacts.import';
    public const CONTACTS_EXPORT      = 'contacts.export';

    // ---- Companies ----
    public const COMPANIES_VIEW   = 'companies.view';
    public const COMPANIES_CREATE = 'companies.create';
    public const COMPANIES_UPDATE = 'companies.update';
    public const COMPANIES_DELETE = 'companies.delete';
    public const COMPANIES_IMPORT = 'companies.import';
    public const COMPANIES_EXPORT = 'companies.export';

    // ---- Agents ----
    public const AGENTS_VIEW        = 'agents.view';
    public const AGENTS_CREATE      = 'agents.create';
    public const AGENTS_UPDATE      = 'agents.update';
    public const AGENTS_DELETE      = 'agents.delete';
    public const AGENTS_BULK_CREATE = 'agents.bulk_create';

    // ---- Groups ----
    public const GROUPS_VIEW   = 'groups.view';
    public const GROUPS_CREATE = 'groups.create';
    public const GROUPS_UPDATE = 'groups.update';
    public const GROUPS_DELETE = 'groups.delete';

    // ---- Roles ----
    public const ROLES_VIEW = 'roles.view';

    // ---- Time entries ----
    public const TIME_ENTRIES_VIEW   = 'time_entries.view';
    public const TIME_ENTRIES_CREATE = 'time_entries.create';
    public const TIME_ENTRIES_UPDATE = 'time_entries.update';
    public const TIME_ENTRIES_DELETE = 'time_entries.delete';

    // ---- Reports ----
    public const REPORTS_VIEW   = 'reports.view';
    public const REPORTS_EXPORT = 'reports.export';

    // ---- Audit log ----
    public const AUDIT_VIEW = 'audit.view';

    // ---- System (superadmin only) ----
    public const SYSTEM_FRESHDESK_VIEW   = 'system.freshdesk.view';
    public const SYSTEM_FRESHDESK_UPDATE = 'system.freshdesk.update';
    public const SYSTEM_MANAGERS_VIEW    = 'system.managers.view';
    public const SYSTEM_MANAGERS_CREATE  = 'system.managers.create';
    public const SYSTEM_MANAGERS_UPDATE  = 'system.managers.update';
    public const SYSTEM_MANAGERS_DELETE  = 'system.managers.delete';
    public const SYSTEM_SYNC_VIEW        = 'system.sync_jobs.view';
    public const SYSTEM_SYNC_RUN         = 'system.sync_jobs.run';
    public const SYSTEM_SETTINGS_VIEW    = 'system.settings.view';
    public const SYSTEM_SETTINGS_UPDATE  = 'system.settings.update';
    public const SYSTEM_API_KEYS_VIEW    = 'system.api_keys.view';
    public const SYSTEM_API_KEYS_CREATE  = 'system.api_keys.create';
    public const SYSTEM_API_KEYS_ROTATE  = 'system.api_keys.rotate';
    public const SYSTEM_API_KEYS_REVOKE  = 'system.api_keys.revoke';

    // ---- Portal (customer) ----
    public const PORTAL_REQUESTS_VIEW_OWN     = 'portal.requests.view_own';
    public const PORTAL_REQUESTS_VIEW_COMPANY = 'portal.requests.view_company';
    public const PORTAL_REQUESTS_CREATE      = 'portal.requests.create';
    public const PORTAL_REQUESTS_REPLY       = 'portal.requests.reply';
    public const PORTAL_REQUESTS_RESOLVE     = 'portal.requests.resolve';
    public const PORTAL_REQUESTS_REOPEN      = 'portal.requests.reopen';
    public const PORTAL_REQUESTS_RATE        = 'portal.requests.rate';
    public const PORTAL_PROFILE_UPDATE       = 'portal.profile.update';

    /**
     * Flat catalog keyed by group. Source of truth for seeders + UI.
     *
     * @return array<string, array<int, string>>
     */
    public static function catalog(): array
    {
        return [
            'tickets' => [
                self::TICKETS_VIEW, self::TICKETS_CREATE, self::TICKETS_UPDATE,
                self::TICKETS_DELETE, self::TICKETS_HARD_DELETE, self::TICKETS_RESTORE,
                self::TICKETS_BULK_UPDATE, self::TICKETS_BULK_DELETE, self::TICKETS_MERGE,
                self::TICKETS_FORWARD, self::TICKETS_OUTBOUND_EMAIL, self::TICKETS_ASSIGN,
            ],
            'conversations' => [
                self::CONVERSATIONS_REPLY, self::CONVERSATIONS_NOTE,
                self::CONVERSATIONS_UPDATE, self::CONVERSATIONS_DELETE,
            ],
            'contacts' => [
                self::CONTACTS_VIEW, self::CONTACTS_CREATE, self::CONTACTS_UPDATE,
                self::CONTACTS_DELETE, self::CONTACTS_HARD_DELETE, self::CONTACTS_RESTORE,
                self::CONTACTS_MERGE, self::CONTACTS_SEND_INVITE, self::CONTACTS_MAKE_AGENT,
                self::CONTACTS_IMPORT, self::CONTACTS_EXPORT,
            ],
            'companies' => [
                self::COMPANIES_VIEW, self::COMPANIES_CREATE, self::COMPANIES_UPDATE,
                self::COMPANIES_DELETE, self::COMPANIES_IMPORT, self::COMPANIES_EXPORT,
            ],
            'agents' => [
                self::AGENTS_VIEW, self::AGENTS_CREATE, self::AGENTS_UPDATE,
                self::AGENTS_DELETE, self::AGENTS_BULK_CREATE,
            ],
            'groups' => [
                self::GROUPS_VIEW, self::GROUPS_CREATE, self::GROUPS_UPDATE, self::GROUPS_DELETE,
            ],
            'roles' => [self::ROLES_VIEW],
            'time_entries' => [
                self::TIME_ENTRIES_VIEW, self::TIME_ENTRIES_CREATE,
                self::TIME_ENTRIES_UPDATE, self::TIME_ENTRIES_DELETE,
            ],
            'reports' => [self::REPORTS_VIEW, self::REPORTS_EXPORT],
            'audit'   => [self::AUDIT_VIEW],
            'system'  => [
                self::SYSTEM_FRESHDESK_VIEW, self::SYSTEM_FRESHDESK_UPDATE,
                self::SYSTEM_MANAGERS_VIEW, self::SYSTEM_MANAGERS_CREATE,
                self::SYSTEM_MANAGERS_UPDATE, self::SYSTEM_MANAGERS_DELETE,
                self::SYSTEM_SYNC_VIEW, self::SYSTEM_SYNC_RUN,
                self::SYSTEM_SETTINGS_VIEW, self::SYSTEM_SETTINGS_UPDATE,
                self::SYSTEM_API_KEYS_VIEW, self::SYSTEM_API_KEYS_CREATE,
                self::SYSTEM_API_KEYS_ROTATE, self::SYSTEM_API_KEYS_REVOKE,
            ],
            'portal' => [
                self::PORTAL_REQUESTS_VIEW_OWN, self::PORTAL_REQUESTS_VIEW_COMPANY,
                self::PORTAL_REQUESTS_CREATE, self::PORTAL_REQUESTS_REPLY,
                self::PORTAL_REQUESTS_RESOLVE, self::PORTAL_REQUESTS_REOPEN,
                self::PORTAL_REQUESTS_RATE, self::PORTAL_PROFILE_UPDATE,
            ],
        ];
    }
}
