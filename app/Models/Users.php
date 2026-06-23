<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Users extends Authenticatable implements JWTSubject
{
    use Notifiable;

    public const TABLE = 'users';
    public const FILEROOT = 'uploads/users';
    public const IS_ADD = true;
    public const IS_EDIT = true;
    public const IS_LIST = true;
    public const IS_DELETE = true;
    public const FIELD_LIST = ['id', 'fullname', 'username', 'email', 'role_id', 'photo', 'active', 'created_at', 'updated_at'];
    public const FIELD_ADD = ['fullname', 'username', 'email', 'password', 'role_id', 'photo', 'active'];
    public const FIELD_EDIT = ['fullname', 'username', 'email', 'password', 'role_id', 'photo', 'active'];
    public const FIELD_VIEW = ['id', 'fullname', 'username', 'email', 'role_id', 'photo', 'active', 'created_at', 'updated_at'];
    public const FIELD_READONLY = ['id', 'created_at', 'updated_at'];
    public const FIELD_FILTERABLE = [
        'role_id' => ['operator' => '='],
        'active' => ['operator' => '='],
    ];
    public const FIELD_SEARCHABLE = ['fullname', 'username', 'email'];
    public const FIELD_SORTABLE = ['id', 'fullname', 'username', 'email', 'created_at', 'updated_at'];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'fullname' => 'string',
        'username' => 'string',
        'email' => 'string',
        'password' => 'password',
        'role_id' => 'integer',
        'photo' => 'file',
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
    ];
    public const FIELD_VALIDATION = [
        'fullname' => 'required|string|max:100',
        'username' => 'required|string|max:50',
        'email' => 'required|email|max:100',
        'password' => 'nullable|string|min:6|max:255',
        'role_id' => 'required|integer',
        'photo' => 'nullable|string|max:255',
        'active' => 'nullable|boolean',
    ];
    public const FIELD_UNIQUE = [
        ['username'],
        ['email'],
    ];
    public const FIELD_UPLOAD = ['photo'];
    public const FIELD_ARRAY = [];
    public const FIELD_DEFAULT_VALUE = [
        'active' => true,
    ];
    public const CHILD_TABLE = [];
    public const CUSTOM_SELECT = '';
    public const CUSTOM_LIST_FILTER = [];
    public const PARENT_CHILD = [];

    protected $table = self::TABLE;
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public static function beforeInsert(array $input): array
    {
        if (empty($input['password'])) {
            throw new \App\CoreService\CoreException(__('message.passwordRequired'));
        }

        $input['password'] = Hash::make($input['password']);
        $input['active'] = filter_var($input['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $input;
    }

    public static function beforeUpdate(array $input): array
    {
        if (array_key_exists('password', $input)) {
            if (blank($input['password'])) {
                unset($input['password']);
            } else {
                $input['password'] = Hash::make($input['password']);
            }
        }

        if (array_key_exists('active', $input)) {
            $input['active'] = filter_var($input['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return $input;
    }

    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    public static function afterInsert(self $object, array $input): void {}
    public static function afterUpdate(self $object, array $input): array { return []; }
    public static function beforeDelete(self $object, array $input): void {}
    public static function afterDelete(self $object, array $input): void {}
    public static function beforeList(array $input): array { return $input; }
    public static function afterDetil(array $input, object $object): object { return $object; }
    public static function getCustomSelect(): string { return ''; }
    public static function getCustomListFilter(): array { return []; }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}