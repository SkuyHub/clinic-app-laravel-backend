<?php

namespace App\Services\Crud;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Services\Crud\Concerns\HandlesFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class Edit extends CoreService
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

        if (!$modelClass::IS_EDIT) {
            throw new CoreException("Editing is not allowed for this model.", 403);
        }

        if (!hasPermission("update-{$input['model']}")) {
            throw new CoreException(__('message.403'), 403);
        }

        if (empty($input['id'])) {
            throw new CoreException('ID is required.', 422);
        }

        $existing = DB::table($modelClass::TABLE)->where('id', $input['id'])->first();
        if (!$existing) {
            throw new CoreException(__('message.dataNotFound', ['id' => $input['id']]), 404);
        }

        $rules = [];
        foreach ($modelClass::FIELD_VALIDATION as $field => $rule) {
            if (array_key_exists($field, $input)) {
                $rules[$field] = $rule;
            }
        }

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            throw new CoreException($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        foreach ($modelClass::FIELD_UNIQUE as $uniqueFields) {
            $query = DB::table($modelClass::TABLE)->where('id', '!=', $input['id']);
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

        $input = $modelClass::beforeUpdate($input);

        $this->modelClass = $modelClass;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $modelClass = $this->modelClass;

        foreach ($modelClass::FIELD_UPLOAD as $field) {
            if ($this->isTempUpload($input[$field] ?? null)) {
                $existing = DB::table($modelClass::TABLE)->where('id', $input['id'])->value($field);
                $this->deleteFileIfExists($existing);
                $input[$field] = $this->moveTempFileToFinalPath($input[$field], $modelClass::TABLE);
            }
        }

        $dataToUpdate = [];
        foreach ($modelClass::FIELD_EDIT as $field) {
            if (array_key_exists($field, $input)) {
                $dataToUpdate[$field] = $input[$field];
            }
        }

        $dataToUpdate['updated_at'] = now();

        DB::table($modelClass::TABLE)->where('id', $input['id'])->update($dataToUpdate);

        $object = $modelClass::find($input['id']);
        $extra = $modelClass::afterUpdate($object, $input);

        $result = CallService::call('App\Services\Crud\Find', [
            'model' => $originalData['model'],
            'id' => $input['id'],
        ]);

        return is_array($extra) && !empty($extra) ? array_merge($result, $extra) : $result;
    }
}