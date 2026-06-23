<?php

namespace App\Services\Auth;

use App\CoreService\CoreService;
use Tymon\JWTAuth\Facades\JWTAuth;

class DoLogout extends CoreService
{
    protected function prepare($input)
    {
        return $input;
    }

    protected function process($input, $originalData)
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return [
            'success' => true,
            'message' => (__('message.logoutSuccess')),
        ];
    }
}
