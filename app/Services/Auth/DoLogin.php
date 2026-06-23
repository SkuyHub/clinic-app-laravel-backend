<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Users;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class DoLogin extends CoreService
{
    protected function validation()
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    protected function prepare($input)
    {
        $user = Users::where('username', $input['username'])
            ->orWhere('email', $input['username'])
            ->first();

        if (!$user) {
            throw new CoreException(__('message.invalidCredentials'), 401);
        }

        if (!$user->active) {
            throw new CoreException(__('message.accountInactive'), 403);
        }

        if (!Hash::check($input['password'], $user->password)) {
            throw new CoreException(__('message.invalidCredentials'), 401);
        }

        $input['user'] = $user;
        return $input;
    }

    protected function process($input, $originalData)
    {
        $token = JWTAuth::fromUser($input['user']);

        return [
            'success' => true,
            'token' => $token,
            'data' => [
                'id' => $input['user']->id,
                'fullname' => $input['user']->fullname,
                'username' => $input['user']->username,
                'email' => $input['user']->email,
                'role_id' => $input['user']->role_id,
                'photo' => $input['user']->photo,
            ],
        ];
    }
}