<?php

namespace App\Models;

use App\Models\Concerns\NormalizesAuthFields;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Patients extends Authenticatable implements JWTSubject
{
    use Notifiable, NormalizesAuthFields;

    public const TABLE = 'patients';
    public const IS_ADD = true;
    public const IS_EDIT = true;
    public const IS_LIST = true;
    public const IS_DELETE = true;
    public const FIELD_LIST = ['id', 'fullname', 'email', 'phone', 'birthdate', 'gender', 'address', 'photo', 'created_at', 'updated_at'];
    public const FIELD_ADD = ['fullname', 'email', 'password', 'phone', 'birthdate', 'gender', 'address', 'photo'];
    public const FIELD_EDIT = ['fullname', 'email', 'password', 'phone', 'birthdate', 'gender', 'address', 'photo'];
    public const FIELD_VIEW = ['id', 'fullname', 'email', 'phone', 'birthdate', 'gender', 'address', 'photo', 'created_at', 'updated_at'];
    public const FIELD_FILTERABLE = [
        'gender' => ['operator' => '='],
    ];
    public const FIELD_SEARCHABLE = ['fullname', 'email', 'address'];
    public const FIELD_SORTABLE = ['id', 'fullname', 'created_at', 'updated_at'];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'fullname' => 'string',
        'email' => 'string',
        'password' => 'password',
        'phone' => 'string',
        'birthdate' => 'date',
        'gender' => 'string',
        'address' => 'string',
        'photo' => 'file',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public const FIELD_RELATION = [];
    public const FIELD_VALIDATION = [
        'fullname' => 'required|string|max:100',
        'email' => 'required|email|max:100',
        'password' => 'nullable|string|min:6|max:255',
        'phone' => 'nullable|string|max:15',
        'birthdate' => 'nullable|date',
        'gender' => 'nullable|in:male,female',
        'address' => 'nullable|string|max:255',
        'photo' => 'nullable|string|max:255',
    ];
    public const FIELD_UNIQUE = [
        ['email'],
    ];
    public const FIELD_UPLOAD = ['photo'];
    public const FIELD_DEFAULT_VALUE = [];

    protected $table = self::TABLE;
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'birthdate' => 'date',
    ];

    public static function beforeInsert(array $input): array
    {
        if (empty($input['password'])) {
            throw new \App\CoreService\CoreException(__('message.passwordRequired'));
        }

        $input['password'] = Hash::make($input['password']);

        return $input;
    }

    public static function beforeUpdate(array $input): array
    {
        return self::normalizePasswordOnUpdate($input);
    }

    public static function afterInsert(self $object, array $input): void {}
    public static function afterUpdate(self $object, array $input): array { return []; }
    public static function beforeDelete(self $object, array $input): void {}
    public static function afterDelete(self $object, array $input): void {}
    public static function beforeList(array $input): array { return $input; }
    public static function afterDetil(array $input, object $object): object { return $object; }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}