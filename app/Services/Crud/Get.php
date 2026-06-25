<?php

namespace App\Services\Crud;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Get extends CoreService
{
    protected $modelClass;

    protected function prepare($input)
    {
        $modelName = Str::studly($input['model']);
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            throw new CoreException("Model {$modelName} not found.", 404);
        }

        if (!$modelClass::IS_LIST) {
            throw new CoreException("Listing is not allowed for this model.", 403);
        }

        if (!hasPermission("view-{$input['model']}")) {
            throw new CoreException(__('message.403'), 403);
        }

        $this->modelClass = $modelClass;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::TABLE;

        $page = (int) ($input['page'] ?? 1);
        $limit = (int) ($input['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $query = DB::table($table)->select(
            array_map(fn($f) => "{$table}.{$f}", $modelClass::FIELD_LIST)
        );

        foreach ($modelClass::FIELD_RELATION as $field => $relation) {
            $query->leftJoin(
                "{$relation['linkTable']} as {$relation['aliasTable']}",
                "{$table}.{$field}",
                '=',
                "{$relation['aliasTable']}.{$relation['linkField']}",
            );

            $query->selectRaw("CONCAT_WS('', " . implode(', ', array_map(
                fn($f) => "{$relation['aliasTable']}.{$f}",
                $relation['selectFields']
            )) . ") as {$relation['displayName']}");
        }

        if (!empty($input['search']) && !empty($modelClass::FIELD_SEARCHABLE)) {
            $search = $input['search'];
            $query->where(function ($q) use ($modelClass, $table, $search) {
                foreach ($modelClass::FIELD_SEARCHABLE as $field) {
                    $q->orWhereRaw("LOWER({$table}.{$field}) LIKE ?", ['%' . strtolower($search) . '%']);
                }
            });
        }

        foreach ($modelClass::FIELD_FILTERABLE as $field => $config) {
           if (isset($input[$field]) && $input[$field] !== '') {
            $query->where("{$table}.{$field}", $config['operator'], $input[$field]);
           }
        }

        $total = $query->count();

        $sortField = $input['sort'] ?? 'id';
        $sortDir = $input['order'] ?? 'asc';

        if (in_array($sortField, $modelClass::FIELD_SORTABLE)) {
            $query->orderBy("{$table}.{$sortField}", $sortDir === 'desc' ? 'desc' : 'asc');
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