<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTask extends BaseModel
{

    public const TABLE = 'role_task';
    public const IS_ADD = true;
    public const IS_EDIT = false;
    public const IS_LIST = true;
    public const FIELD_LIST = [
        'id',
        'role_id',
        'task_id',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_VIEW = [
        'id',
        'role_id',
        'task_id',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_FILTERABLE = [
        'role_id' => ['operator' => '='],
        'task_id' => ['operator' => '='],
        'active' => ['operator' => '='],
    ];
    public const FIELD_SORTABLE = [
        'id',
        'role_id',
        'task_id',
        'active',
        'created_at',
        'updated_at'
    ];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'role_id' => 'integer',
        'task_id' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public const FIELD_RELATION = [
        'role_id' => [
            'linkTable' => 'roles',
            'linkField' => 'id',
            'aliasTable' => 'roles_ref',
            'displayName' => 'rel_role_id',
            'selectFields' => ['role_name'], 
        ],
        'task_id' => [
            'linkTable' => 'tasks',
            'linkField' => 'id',
            'aliasTable' => 'tasks_ref',
            'displayName' => 'rel_task_id',
            'selectFields' => ['task_code', 'task_name'],
        ],
    ];


    protected $table = self::TABLE;
    protected $casts = [
        'active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }
}
