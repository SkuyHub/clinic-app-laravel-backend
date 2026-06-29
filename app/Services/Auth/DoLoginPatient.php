<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Patients;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class DoLoginPatient extends CoreService
{
    protected function validation()
    {
        return [
            'email' => 'required|string',
            'password' => 'required|string',
        ];
    }

    protected function prepare($input)
    {
        $input['email'] = strtolower($input['email']);

        $patient = Patients::where('email', $input['email'])->first();

        if (!$patient) {
            throw new CoreException(__('message.invalidCredentials'), 401);
        }

        if (!Hash::check($input['password'], $patient->password)) {
            throw new CoreException(__('message.invalidCredentials'), 401);
        }

        $input['patient'] = $patient;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $token = JWTAuth::fromUser($input['patient']);

        return [
            'success' => true,
            'token' => $token,
            'data' => [
                'id' => $input['patient']->id,
                'fullname' => $input['patient']->fullname,
                'email' => $input['patient']->email,
            ],
        ];
    }
}