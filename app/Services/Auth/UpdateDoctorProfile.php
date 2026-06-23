<?php

namespace App\Services\Auth;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Doctors;
use App\Services\Crud\Concerns\HandlesFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateDoctorProfile extends CoreService
{
    use HandlesFileUploads;

    public $transaction = true;

    protected const EDITABLE_FIELDS = ['fullname', 'email', 'password', 'phone', 'photo', 'available'];

    protected function prepare($input)
    {
        $doctor = Auth::user();

        if (!$doctor instanceof Doctors) {
            throw new CoreException(__('message.403'), 403);
        }

        $rules = [];
        foreach (Doctors::FIELD_VALIDATION as $field => $rule) {
            if (in_array($field, self::EDITABLE_FIELDS) && array_key_exists($field, $input)) {
                $rules[$field] = $rule;
            }
        }

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            throw new CoreException($validator->errors()->first(), 422, $validator->errors()->toArray());
        }

        if (array_key_exists('email', $input) && $input['email'] !== $doctor->email) {
            $exists = DB::table('doctors')->where('email', $input['email'])->where('id', '!=', $doctor->id)->exists();
            if ($exists) {
                throw new CoreException('This email is already in use.', 422);
            }
        }

        $input = Doctors::normalizePasswordOnUpdate($input);
        $input = Doctors::normalizeBooleanField($input, 'available');

        $input['_doctor_id'] = $doctor->id;
        $input['_current_photo'] = $doctor->photo;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $doctorId = $input['_doctor_id'];

        if ($this->isTempUpload($input['photo'] ?? null)) {
            $this->deleteFileIfExists($input['_current_photo']);
            $input['photo'] = $this->moveTempFileToFinalPath($input['photo'], 'doctors');
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
        DB::table('doctors')->where('id', $doctorId)->update($dataToUpdate);

        return CallService::call('App\Services\Crud\Find', [
            'model' => 'doctors',
            'id' => $doctorId,
        ]);
    }
}