<?php

namespace App\Traits;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasDataTableQueries
{
    /**
     * Apply sorting, per-page limit, and pagination with preserved query strings.
     *
     * @param  array<string, string|Closure>  $sortableColumns  Map of request sort keys to column names or closures
     */
    public function applyDataTable(
        Builder $query,
        Request $request,
        array $sortableColumns = [],
        string $defaultSort = 'id',
        string $defaultDirection = 'asc',
        int $defaultPerPage = 15
    ): LengthAwarePaginator {
        // 1. Sanitize entries per page
        $allowedPerPage = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', $defaultPerPage);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = $defaultPerPage;
        }

        // 2. Determine sort key & direction
        $sort = (string) $request->input('sort', $defaultSort);
        $direction = strtolower((string) $request->input('direction', $defaultDirection)) === 'desc' ? 'desc' : 'asc';

        // 3. Apply sorting safely from whitelist
        if (array_key_exists($sort, $sortableColumns)) {
            $target = $sortableColumns[$sort];
            if ($target instanceof Closure) {
                $target($query, $direction);
            } else {
                $query->orderBy($target, $direction);
            }
        } elseif (array_key_exists($defaultSort, $sortableColumns)) {
            $target = $sortableColumns[$defaultSort];
            if ($target instanceof Closure) {
                $target($query, $defaultDirection);
            } else {
                $query->orderBy($target, $defaultDirection);
            }
        } else {
            $query->orderBy($defaultSort, $defaultDirection);
        }

        // Always paginate and preserve all GET query parameters
        return $query->paginate($perPage)->withQueryString();
    }
}
