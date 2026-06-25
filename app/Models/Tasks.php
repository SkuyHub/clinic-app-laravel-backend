<?php

namespace App\Models;

class Tasks extends BaseModel
{
    public const TABLE = 'tasks';
    public const FIELD_LIST = [
        'id',
        'task_code',
        'task_name',
        'module',
        'description',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_ADD = [
        'task_code',
        'task_name',
        'module',
        'description',
        'active'
    ];
    public const FIELD_EDIT = [
        'task_code',
        'task_name',
        'module',
        'description',
        'active'
    ];
    public const FIELD_VIEW = [
        'id',
        'task_code',
        'task_name',
        'module',
        'description',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_FILTERABLE = [
        'module' => ['operator' => '='],
        'active' => ['operator' => '='],
    ];
    public const FIELD_SEARCHABLE = [
        'task_code',
        'task_name',
        'module'
    ];
    public const FIELD_SORTABLE = [
        'id',
        'task_code',
        'task_name',
        'module',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'task_code' => 'string',
        'task_name' => 'string',
        'module' => 'string',
        'description' => 'string',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public const FIELD_VALIDATION = [
        'task_code' => 'required|string|max:50',
        'task_name' => 'required|string|max:100',
        'module' => 'required|string|max:100',
        'description' => 'nullable|string',
        'active' => 'nullable|boolean',
    ];
    public const FIELD_UNIQUE = [
        ['task_code'],
    ];
    public const FIELD_DEFAULT_VALUE = [
        'active' => true,
    ];

    protected $table = self::TABLE;
    protected $casts = [
        'active' => 'boolean',
    ];

    public static function beforeInsert(array $input): array
    {
        $input['active'] = filter_var($input['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $input;
    }

    public static function beforeUpdate(array $input): array 
    {
        if (array_key_exists('active', $input)) {
            $input['active'] = filter_var($input['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        return $input;
    }
}
