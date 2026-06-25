<?php

namespace App\Services\Auth;

use App\CoreService\CoreService;
use Illuminate\Support\Facades\DB;

class ListAvailableDoctors extends CoreService
{
    protected function prepare($input)
    {
        return $input;
    }

    protected function process($input, $originalData)
    {
        $doctors = DB::table('doctors')
            ->where('available', true)
            ->select('id', 'fullname', 'specialization', 'email')
            ->get();

        return [
            'success' => true,
            'data' => $doctors,
        ];
    }
}
