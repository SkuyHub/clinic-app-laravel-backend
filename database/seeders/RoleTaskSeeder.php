<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoleTaskSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $receptionistRoleId = DB::table('roles')->insertGetId([
            'role_code' => 'receptionist',
            'role_name' => 'Receptionist',
            'description' => 'Manages appointments, patients, and rooms. No access to medical records or user administration.',
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $allowedTaskCodes = [];
        foreach (['appointments', 'patients', 'rooms', 'doctors'] as $module) {
            foreach (['view', 'show', 'create', 'update'] as $verb) {
                $allowedTaskCodes[] = "{$verb}-{$module}";
            }
        }

        $taskIds = DB::table('tasks')
            ->whereIn('task_code', $allowedTaskCodes)
            ->pluck('id', 'task_code');

        $rows = [];
        foreach ($taskIds as $taskId) {
            $rows[] = [
                'role_id' => $receptionistRoleId,
                'task_id' => $taskId,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('role_task')->insert($rows);

        DB::table('users')->insert([
            'fullname' => 'Front Desk',
            'username' => 'receptionist',
            'email' => 'receptionist@clinic.test',
            'password' => Hash::make('Reception~2026'),
            'role_id' => $receptionistRoleId,
            'photo' => null,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}