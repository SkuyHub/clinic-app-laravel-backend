<?php

namespace App\Services\Crud;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Delete extends CoreService
{
    public $transaction = true;

    protected $modelClass;

    protected function prepare($input)
    {
        $modelName = Str::studly($input['model']);
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            throw new CoreException("Model {$modelName} not found.", 404);
        }

        if (!$modelClass::IS_DELETE) {
            throw new CoreException("Deleting is not allowed for this model.", 403);
        }

        if (!hasPermission("delete-{$input['model']}")) {
            throw new CoreException(__('message.403'), 403);
        }

        if (empty($input['id'])) {
            throw new CoreException('ID is required.', 422);
        }

        $existing = $modelClass::find($input['id']);
        if (!$existing) {
            throw new CoreException(__('message.dataNotFound', ['id' => $input['id']]), 404);
        }

        $this->modelClass = $modelClass;
        $input['_object'] = $existing;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $modelClass = $this->modelClass;
        $object = $input['_object'];

        $modelClass::beforeDelete($object, $input);

        try {
            DB::table($modelClass::TABLE)->where('id', $input['id'])->delete();
        } catch (QueryException $ex) {
            throw new CoreException(
                'This record cannot be deleted because it is referenced by other data.',
                409
            );
        }

        $modelClass::afterDelete($object, $input);

        return [
            'success' => true,
            'message' => __('message.deleteSuccess'),
        ];
    }
}