<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Patients;
use App\Services\Crud\Concerns\HandlesFileUploads;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterPatient extends CoreService
{
    use HandlesFileUploads;

    public $transaction = true;

    protected function validation()
    {
        return [
            'fullname' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'password' => 'required|string|min:6|max:255',
            'phone' => 'nullable|string|max:15',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string|max:255',
            'photo' => 'nullable|string|max:255',
        ];
    }

    protected function prepare($input)
    {
        $input['email'] = strtolower($input['email']);

        $exists = DB::table('patients')->where('email', $input['email'])->exists();
        if ($exists) {
            throw new CoreException('Email already registered.', 422);
        }

        return $input;
    }

    protected function process($input, $originalData)
    {
        if ($this->isTempUpload($input['photo'] ?? null)) {
            $input['photo'] = $this->moveTempFileToFinalPath($input['photo'], 'patients');
        }

        $input = Patients::beforeInsert($input);

        $dataToInsert = [];
        foreach (Patients::FIELD_ADD as $field) {
            if (array_key_exists($field, $input)) {
                $dataToInsert[$field] = $input[$field];
            }
        }

        $dataToInsert['created_at'] = now();
        $dataToInsert['updated_at'] = now();

        $id = DB::table('patients')->insertGetId($dataToInsert);

        $patient = Patients::find($id);

        $token = JWTAuth::fromUser($patient);

        return [
            'success' => true,
            'token' => $token,
            'data' => [
                'id' => $patient->id,
                'fullname' => $patient->fullname,
                'email' => $patient->email,
            ],
        ];
    }
}
