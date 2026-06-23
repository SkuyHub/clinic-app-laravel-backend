<?php

namespace App\Services\Auth;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Patients;
use App\Services\Crud\Concerns\HandlesFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdatePatientProfile extends CoreService
{
    use HandlesFileUploads;

    public $transaction = true;

    protected const EDITABLE_FIELDS = ['fullname', 'email', 'password', 'phone', 'birthdate', 'gender', 'address', 'photo'];

    protected function prepare($input)
    {
        $patient = Auth::user();

        if (!$patient instanceof Patients) {
            throw new CoreException(__('message.403'), 403);
        }

        $rules = [];
        foreach (Patients::FIELD_VALIDATION as $field => $rule) {
            if (in_array($field, self::EDITABLE_FIELDS) && array_key_exists($field, $input)) {
                $rules[$field] = $rule;
            }
        }

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            throw new CoreException($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        if (array_key_exists('email', $input) && $input['email'] !== $patient->email) {
            $exists = DB::table('patients')->where('email', $input['email'])->where('id', '!=', $patient->id)->exists();
            if ($exists) {
                throw new CoreException('This email is already in use.', 422);
            }
        }

        $input = Patients::normalizePasswordOnUpdate($input);

        $input['_patient_id'] = $patient->id;
        $input['_current_photo'] = $patient->photo;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $patientId = $input['_patient_id'];

        if ($this->isTempUpload($input['photo'] ?? null)) {
            $this->deleteFileIfExists($input['_current_photo']);
            $input['photo'] = $this->moveTempFileToFinalPath($input['photo'], 'patients');
        }

        $dataToUpdate = [];
        foreach (self::EDITABLE_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $dataToUpdate[$field] = $input[$field];
            }
        }

        if (empty($dataToUpdate)) {
            throw new CoreException('No valid fields to update.', 422);
        }

        $dataToUpdate['updated_at'] = now();
        DB::table('patients')->where('id', $patientId)->update($dataToUpdate);

        return CallService::call('App\Services\Crud\Find', [
            'model' => 'patients',
            'id' => $patientId,
        ]);
    }
}