<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared pagination / search / ordering helpers for admin list endpoints.
 * Callers pass a builder already narrowed by base filters and scope; we apply
 * pagination (offset/limit) and return a `{data, meta}` envelope.
 */
final class ApiQuery
{
    public const DEFAULT_PAGE_SIZE = 25;
    public const MAX_PAGE_SIZE = 100;

    /**
     * Apply cursor-style pagination via `cursor` (interpreted as an integer
     * offset for simplicity; see migration note in the spec for proper cursor
     * implementation).
     *
     * @param  array<string,mixed>  $params
     * @return array{data: array<int,mixed>, meta: array<string,mixed>}
     */
    public static function page(Builder $q, array $params, array $with = []): array
    {
        $limit = min(self::MAX_PAGE_SIZE, (int) ($params['per_page'] ?? self::DEFAULT_PAGE_SIZE));
        if ($limit <= 0) $limit = self::DEFAULT_PAGE_SIZE;

        $offset = (int) ($params['cursor'] ?? 0);

        $total = (clone $q)->count();
        if ($with) $q->with($with);

        $rows = $q->skip($offset)->take($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return [
            'data' => $rows->all(),
            'meta' => [
                'total'       => $total,
                'next_cursor' => $hasMore ? $offset + $limit : null,
                'per_page'    => $limit,
            ],
        ];
    }

    public static function applySearch(Builder $q, ?string $search, array $columns): Builder
    {
        if (! $search) return $q;
        $term = '%'.trim($search).'%';
        $q->where(function ($w) use ($columns, $term) {
            foreach ($columns as $col) $w->orWhere($col, 'like', $term);
        });
        return $q;
    }

    public static function applyOrderBy(Builder $q, ?string $sort, array $allowed, string $default = 'id'): Builder
    {
        $dir = 'desc';
        if ($sort && str_starts_with($sort, '-')) { $dir = 'desc'; $sort = ltrim($sort, '-'); }
        elseif ($sort && str_starts_with($sort, '+')) { $dir = 'asc'; $sort = ltrim($sort, '+'); }
        if (! $sort || ! in_array($sort, $allowed, true)) $sort = $default;
        return $q->orderBy($sort, $dir);
    }
}
