<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Doctors;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class DoLoginDoctor extends CoreService
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
        $doctor = Doctors::where('email', $input['email'])->first();

        if (!$doctor) {
            throw new CoreException(__('message.invalidCredentials'), 401);
        }

        if (!$doctor->available) {
            throw new CoreException('This account is currently inactive.', 403);
        }

        if (!Hash::check($input['password'], $doctor->password)) {
            throw new CoreException(__('message.invalidCredentials'), 401);
        }

        $input['doctor'] = $doctor;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $token = JWTAuth::fromUser($input['doctor']);

        return [
            'success' => true,
            'token' => $token,
            'data' => [
                'id' => $input['doctor']->id,
                'fullname' => $input['doctor']->fullname,
                'specialization' => $input['doctor']->specialization,
                'email' => $input['doctor']->email,
            ],
        ];
    }
}