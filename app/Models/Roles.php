<?php

namespace App\Models;

class Roles extends BaseModel
{
    public const TABLE = 'roles';
    public const FIELD_LIST = [
        'id',
        'role_code',
        'role_name',
        'description',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_ADD = [
        'role_code',
        'role_name',
        'description',
        'active'
    ];
    public const FIELD_EDIT = [
        'role_code',
        'role_name',
        'description',
        'active'
    ];
    public const FIELD_VIEW = [
        'id',
        'role_code',
        'role_name',
        'description',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_FILTERABLE = [
        'active' => ['operator' => '='],
    ];
    public const FIELD_SEARCHABLE = [
        'role_code',
        'role_name'
    ];
    public const FIELD_SORTABLE = [
        'id',
        'role_code',
        'role_name',
        'description',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'role_code' => 'string',
        'role_name' => 'string',
        'description' => 'string',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public const FIELD_VALIDATION = [
        'role_code' => 'required|string|max:50',
        'role_name' => 'required|string|max:100',
        'description' => 'nullable|string',
        'active' => 'nullable|boolean',
    ];
    public const FIELD_UNIQUE = [
        ['role_code'],
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
