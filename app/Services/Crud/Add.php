<?php

namespace App\Services\Crud;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Services\Crud\Concerns\HandlesFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class Add extends CoreService
{
    use HandlesFileUploads;

    public $transaction = true;

    protected $modelClass;

    protected function prepare($input)
    {
        $modelName = Str::studly($input['model']);
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            throw new CoreException("Model {$modelName} not found.", 404);
        }

        if (!$modelClass::IS_ADD) {
            throw new CoreException("Creating is not allowed for this model.", 403);
        }

        if (!hasPermission("create-{$input['model']}")) {
            throw new CoreException(__('message.403'), 403);
        }

        $validator = Validator::make($input, $modelClass::FIELD_VALIDATION);
        if ($validator->fails()) {
            throw new CoreException($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        foreach ($modelClass::FIELD_UNIQUE as $uniqueFields) {
            $query = DB::table($modelClass::TABLE);
            foreach ($uniqueFields as $field) {
                if (!array_key_exists($field, $input)) {
                    continue 2;
                }
                $query->where($field, $input[$field]);
            }
            if ($query->exists()) {
                $fieldList = implode(', ', $uniqueFields);
                throw new CoreException("The combination of [{$fieldList}] already exists.", 422);
            }
        }

        foreach ($modelClass::FIELD_DEFAULT_VALUE as $field => $value) {
            if (!array_key_exists($field, $input) || $input[$field] === null) {
                $input[$field] = $value;
            }
        }

        $input = $modelClass::beforeInsert($input);

        $this->modelClass = $modelClass;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $modelClass = $this->modelClass;

        foreach ($modelClass::FIELD_UPLOAD as $field) {
            if ($this->isTempUpload($input[$field] ?? null)) {
                $input[$field] = $this->moveTempFileToFinalPath($input[$field], $modelClass::TABLE);
            }
        }

        $dataToInsert = [];
        foreach ($modelClass::FIELD_ADD as $field) {
            if (array_key_exists($field, $input)) {
                $dataToInsert[$field] = $input[$field];
            }
        }

        $dataToInsert['created_at'] = now();
        $dataToInsert['updated_at'] = now();

        $id = DB::table($modelClass::TABLE)->insertGetId($dataToInsert);

        $object = $modelClass::find($id);
        $modelClass::afterInsert($object, $input);

        return CallService::call('App\Services\Crud\Find', [
            'model' => $originalData['model'],
            'id' => $id,
        ]);
    }
}