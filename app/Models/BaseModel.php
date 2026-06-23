<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    public const TABLE = '';
    public const FILEROOT = '';
    public const IS_ADD = true;
    public const IS_EDIT = true;
    public const IS_LIST = true;
    public const IS_DELETE = true;
    public const FIELD_LIST = [];
    public const FIELD_ADD = [];
    public const FIELD_EDIT = [];
    public const FIELD_VIEW = [];
    public const FIELD_READONLY = ['id', 'created_at', 'updated_at'];
    public const FIELD_FILTERABLE = [];
    public const FIELD_SEARCHABLE = [];
    public const FIELD_SORTABLE = ['id'];
    public const FIELD_TYPE = [];
    public const FIELD_RELATION = [];
    public const FIELD_VALIDATION = [];
    public const FIELD_UNIQUE = [];
    public const FIELD_UPLOAD = [];
    public const FIELD_ARRAY = [];
    public const FIELD_DEFAULT_VALUE = [];
    public const CHILD_TABLE = [];
    public const CUSTOM_SELECT = '';
    public const CUSTOM_LIST_FILTER = [];
    public const PARENT_CHILD = [];

    protected $guarded = [];

    public static function beforeInsert(array $input): array
    {
        return $input;
    }
    public static function beforeUpdate(array $input): array
    {
        return $input;
    }
    public static function afterInsert(self $object, array $input): void
    {
    }
    public static function afterUpdate(self $object, array $input): array
    {
        return [];
    }
    public static function beforeDelete(self $object, array $input): void
    {
    }
    public static function afterDelete(self $object, array $input): void
    {
    }
    public static function beforeList(array $input): array
    {
        return $input;
    }
    public static function afterDetil(array $input, object $object): object
    {
        return $object;
    }
    public static function getCustomSelect(): string
    {
        return '';
    }
    public static function getCustomListFilter(): array
    {
        return [];
    }
    
}
