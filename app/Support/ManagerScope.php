<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Applies manager-scope (assigned_group_ids) filters to Eloquent queries.
 * When the request has no scope attribute the user is treated as superadmin
 * (global view); managers always carry `assigned_group_ids` injected by the
 * middleware in bootstrap/app.php.
 */
final class ManagerScope
{
    public static function isManager(): bool
    {
        $user = Auth::user();
        return $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('manager')
            && ! $user->hasRole('superadmin');
    }

    /** @return int[] */
    public static function groupIds(): array
    {
        $ids = Request::attributes->get('assigned_group_ids');
        if (is_array($ids)) return array_values(array_unique(array_map('intval', $ids)));

        $user = Auth::user();
        if ($user && method_exists($user, 'managerGroups')) {
            return $user->managerGroups()->pluck('groups.id')->map(fn ($i) => (int) $i)->all();
        }

        return [];
    }

    public static function applyToTickets(Builder $q, string $column = 'group_id'): Builder
    {
        if (! self::isManager()) return $q;
        $ids = self::groupIds();
        if (empty($ids)) return $q->whereRaw('1 = 0');
        return $q->whereIn($column, $ids);
    }

    /** Contacts: limit to contacts with ≥1 ticket in scope. */
    public static function applyToContacts(Builder $q): Builder
    {
        if (! self::isManager()) return $q;
        $ids = self::groupIds();
        if (empty($ids)) return $q->whereRaw('1 = 0');
        return $q->whereExists(function ($s) use ($ids) {
            $s->selectRaw(1)->from('tickets')
                ->whereColumn('tickets.requester_id', 'contacts.id')
                ->whereIn('tickets.group_id', $ids);
        });
    }

    /** Companies: limit to companies with ≥1 in-scope contact. */
    public static function applyToCompanies(Builder $q): Builder
    {
        if (! self::isManager()) return $q;
        $ids = self::groupIds();
        if (empty($ids)) return $q->whereRaw('1 = 0');
        return $q->whereExists(function ($s) use ($ids) {
            $s->selectRaw(1)->from('contacts')
                ->whereColumn('contacts.company_id', 'companies.id')
                ->whereExists(function ($t) use ($ids) {
                    $t->selectRaw(1)->from('tickets')
                        ->whereColumn('tickets.requester_id', 'contacts.id')
                        ->whereIn('tickets.group_id', $ids);
                });
        });
    }

    /** Agents: agents sharing ≥1 assigned group. Uses the `agents.group_ids` JSON column. */
    public static function applyToAgents(Builder $q): Builder
    {
        if (! self::isManager()) return $q;
        $ids = self::groupIds();
        if (empty($ids)) return $q->whereRaw('1 = 0');
        $q->where(function ($outer) use ($ids) {
            foreach ($ids as $id) {
                $outer->orWhereJsonContains('group_ids', (int) $id);
            }
        });
        return $q;
    }

    public static function applyToGroups(Builder $q): Builder
    {
        if (! self::isManager()) return $q;
        $ids = self::groupIds();
        if (empty($ids)) return $q->whereRaw('1 = 0');
        return $q->whereIn('id', $ids);
    }
}
