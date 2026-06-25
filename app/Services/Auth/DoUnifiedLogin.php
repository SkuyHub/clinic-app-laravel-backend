<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Doctors;
use App\Models\Patients;
use App\Models\Users;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class DoUnifiedLogin extends CoreService
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
        $admin = Users::where('email', $input['email'])
            ->orWhere('username', $input['email'])
            ->first();

        if ($admin && Hash::check($input['password'], $admin->password)) {
            if (!$admin->active) {
                throw new CoreException(__('message.accountInactive'), 403);
            }

            $input['_guard'] = 'api';
            $input['_user'] = $admin;
            $input['_role'] = 'admin';
            $input['_data'] = [
                'id' => $admin->id,
                'fullname' => $admin->fullname,
                'username' => $admin->username,
                'email' => $admin->email,
                'role_id' => $admin->role_id,
                'photo' => $admin->photo,
            ];

            return $input;
        }

        $doctor = Doctors::where('email', $input['email'])->first();

        if ($doctor && Hash::check($input['password'], $doctor->password)) {
            if (!$doctor->available) {
                throw new CoreException('This doctor account is currently inactive.', 403);
            }

            $input['_guard'] = 'doctor';
            $input['_user'] = $doctor;
            $input['_role'] = 'doctor';
            $input['_data'] = [
                'id' => $doctor->id,
                'fullname' => $doctor->fullname,
                'specialization' => $doctor->specialization,
                'email' => $doctor->email,
            ];

            return $input;
        }

        $patient = Patients::where('email', $input['email'])->first();

        if ($patient && Hash::check($input['password'], $patient->password)) {
            $input['_guard'] = 'patient';
            $input['_user'] = $patient;
            $input['_role'] = 'patient';
            $input['_data'] = [
                'id' => $patient->id,
                'fullname' => $patient->fullname,
                'email' => $patient->email,
            ];

            return $input;
        }

        throw new CoreException(__('message.invalidCredentials'), 401);
    }

    protected function process($input, $originalData)
    {
        Config::set('auth.defaults.guard', $input['_guard']);

        $token = JWTAuth::fromUser($input['_user']);

        return [
            'success' => true,
            'token' => $token,
            'role' => $input['_role'],
            'data' => $input['_data'],
        ];
    }
}
