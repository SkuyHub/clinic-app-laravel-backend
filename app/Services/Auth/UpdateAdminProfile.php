<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UpdateAdminProfile extends CoreService
{
    public $transaction = true;

    protected function validation()
    {
        return [
            'fullname' => 'nullable|string|max:100',
            'username' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'password' => 'nullable|string|min:6|max:255',
            'photo' => 'nullable|string|max:255',
        ];
    }

    protected function prepare($input)
    {
        $user = Auth::user();

        if (!empty($input['username']) && $input['username'] !== $user->username) {
            $exists = \App\Models\Users::where('username', $input['username'])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($exists) {
                throw new CoreException('Username already taken.', 422);
            }
        }

        if (!empty($input['email']) && $input['email'] !== $user->email) {
            $exists = \App\Models\Users::where('email', $input['email'])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($exists) {
                throw new CoreException('Email already taken.', 422);
            }
        }

        if (array_key_exists('password', $input)) {
            if (blank($input['password'])) {
                unset($input['password']);
            } else {
                $input['password'] = Hash::make($input['password']);
            }
        }

        return $input;
    }

    protected function process($input, $originalData)
    {
        $user = Auth::user();
        $data = array_intersect_key($input, array_flip(['fullname', 'username', 'email', 'password', 'photo']));

        if (!empty($data)) {
            $user->update($data);
        }

        return [
            'success' => true,
            'data' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'photo' => $user->photo,
            ],
        ];
    }
}
