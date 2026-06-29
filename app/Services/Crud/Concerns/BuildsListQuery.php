<?php

namespace App\Services\Crud\Concerns;

use Illuminate\Support\Facades\DB;

trait BuildsListQuery
{
    protected function buildListQuery(
        string $modelClass,
        array $input,
        string $scopeColumn,
        array $defaultSort = [],
        ?callable $applyFilters = null,
    ): array {
        $table = $modelClass::TABLE;
        $page = (int) ($input['page'] ?? 1);
        $limit = (int) ($input['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $query = DB::table($table)
            ->select(array_map(fn ($f) => "{$table}.{$f}", $modelClass::FIELD_LIST))
            ->where("{$table}.{$scopeColumn}", $input["_{$scopeColumn}"]);

        foreach ($modelClass::FIELD_RELATION as $field => $relation) {
            $query->leftJoin(
                "{$relation['linkTable']} as {$relation['aliasTable']}",
                "{$table}.{$field}",
                '=',
                "{$relation['aliasTable']}.{$relation['linkField']}"
            );

            $query->selectRaw("CONCAT_WS(' ', ".implode(', ', array_map(
                fn ($f) => "{$relation['aliasTable']}.{$f}",
                $relation['selectFields']
            )).") as {$relation['displayName']}");
        }

        if (!empty($input['search']) && !empty($modelClass::FIELD_SEARCHABLE)) {
            $search = $input['search'];
            $query->where(function ($q) use ($table, $modelClass, $search) {
                foreach ($modelClass::FIELD_SEARCHABLE as $field) {
                    $q->orWhereRaw("LOWER({$table}.{$field}) LIKE ?", ['%'.strtolower($search).'%']);
                }
            });
        }

        if ($applyFilters) {
            $applyFilters($query, $input);
        }

        $total = $query->count();

        $sortField = $input['sort'] ?? null;
        $sortDir = $input['order'] ?? 'asc';

        if ($sortField && in_array($sortField, $modelClass::FIELD_SORTABLE)) {
            $query->orderBy("{$table}.{$sortField}", $sortDir === 'desc' ? 'desc' : 'asc');
        } elseif (!empty($defaultSort)) {
            foreach ($defaultSort as [$col, $dir]) {
                $query->orderBy("{$table}.{$col}", $dir);
            }
        } else {
            $query->orderBy("{$table}.id", 'asc');
        }

        $rows = $query->offset($offset)->limit($limit)->get();

        return [
            'success' => true,
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'last_page' => (int) ceil($total / max($limit, 1)),
        ];
    }
}
