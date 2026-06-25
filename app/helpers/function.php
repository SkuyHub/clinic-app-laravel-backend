<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

if (!function_exists('hasPermission')){
    function hasPermission(string $task): bool
    {
        $user = Auth::user();

        if (!$user){
            return false;
        }

        if (!isset($user->role_id) || (int) $user->role_id === 1){
            return true;
        }

        return DB::table('role_task')
        ->join('tasks', 'tasks.id', '=', 'role_task.task_id')
        ->where('role_task.role_id', $user->role_id)
        ->where('role_task.active', true)
        ->where('tasks.active', true)
        ->where('tasks.task_code', $task)
        ->exists();
    }
}