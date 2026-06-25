<?php

namespace App\Models;

class Rooms extends BaseModel
{
    public const TABLE = 'rooms';
    public const FIELD_LIST = ['id', 'room_code', 'room_name', 'capacity', 'available', 'created_at', 'updated_at'];
    public const FIELD_ADD = ['room_code', 'room_name', 'capacity', 'available'];
    public const FIELD_EDIT = ['room_code', 'room_name', 'capacity', 'available'];
    public const FIELD_VIEW = ['id', 'room_code', 'room_name', 'capacity', 'available', 'created_at', 'updated_at'];
    public const FIELD_FILTERABLE = ['available' => ['operator' => '='],];
    public const FIELD_SEARCHABLE = ['room_code', 'room_name'];
    public const FIELD_SORTABLE = ['id', 'room_code', 'room_name', 'capacity', 'available', 'created_at', 'updated_at'];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'room_code' => 'string',
        'room_name' => 'string',
        'capacity' => 'integer',
        'available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public const FIELD_VALIDATION = [
        'room_code' => 'required|string|max:20',
        'room_name' => 'required|string|max:100',
        'capacity' => 'required|integer|min:1',
        'available' => 'nullable|boolean',
    ];
    public const FIELD_UNIQUE = [
        ['room_code'],
    ];
    public const FIELD_DEFAULT_VALUE = [
        'available' => true,
    ];

    protected $table = self::TABLE;
    protected $casts = [
        'available' => 'boolean',
    ];

    public static function beforeInsert(array $input): array
    {
        $input['available'] = filter_var($input['available'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $input;
    }
    public static function beforeUpdate(array $input): array
    {
        if (array_key_exists('available', $input)){
            $input['available'] = filter_var($input['available'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        return $input;
    }

    public function appointments()
    {
        return $this->hasMany(Appointments::class, 'room_id');
    }
}
