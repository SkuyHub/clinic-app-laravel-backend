<?php

namespace App\Services\Crud;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Find extends CoreService
{
    protected $modelClass;

    protected function prepare($input)
    {
        $modelName = Str::studly($input['model']);
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            throw new CoreException("Model {$modelName} not found.", 404);
        }

        if (!hasPermission("show-{$input['model']}")) {
            throw new CoreException(__('message.403'), 403);
        }

        if (empty($input['id'])) {
            throw new CoreException('ID is required.', 422);
        }

        $this->modelClass = $modelClass;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $modelClass = $this->modelClass;
        $table = $modelClass::TABLE;

        $query = DB::table($table)->select(
            array_map(fn($f) => "{$table}.{$f}", $modelClass::FIELD_VIEW)
        );

        foreach ($modelClass::FIELD_RELATION as $field => $relation) {
            $query->leftJoin(
                "{$relation['linkTable']} as {$relation['aliasTable']}",
                "{$table}.{$field}",
                '=',
                "{$relation['aliasTable']}.{$relation['linkField']}"
            );

            $query->selectRaw("CONCAT_WS('', " . implode(', ', array_map(
                fn($f) => "{$relation['aliasTable']}.{$f}",
                $relation['selectFields']
            )) . ") as {$relation['displayName']}");
        }

        $row = $query->where("{$table}.id", $input['id'])->first();

        if (!$row) {
            throw new CoreException(__('message.dataNotFound', ['id' => $input['id']]), 404);
        }

        $row = $modelClass::afterDetil($input, $row);

        return [
            'success' => true,
            'data' => $row,
        ];
    }
}
