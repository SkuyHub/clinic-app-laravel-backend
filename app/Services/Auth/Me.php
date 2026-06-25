<?php

namespace App\Services\Auth;

use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Me extends CoreService
{
    /**
     * Create a new class instance.
     */
    protected function prepare($input)
    {
        return $input;
    }

    protected function process($input, $originalData)
    {
        $user = Auth::user();

        if ((int) $user->role_id === 1) {
            $permissions = DB::table('tasks')
                ->where('active', true)
                ->pluck('task_code')
                ->toArray();
        } else {
            $permissions = DB::table('role_task')
                ->join('tasks', 'tasks.id', '=', 'role_task.task_id')
                ->where('role_task.role_id', $user->role_id)
                ->where('role_task.active', true)
                ->where('tasks.active', true)
                ->pluck('tasks.task_code')
                ->toArray();
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
            'permissions' => $permissions,
        ];
    }
}
