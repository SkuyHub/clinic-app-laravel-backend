<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            'roles' => 'Roles',
            'tasks' => 'Tasks',
            'role-task' => 'Role-Task Mapping',
            'users' => 'Users',
            'doctors' => 'Doctors',
            'patients' => 'Patients',
            'rooms' => 'Rooms',
            'appointments' => 'Appointments',
            'medicalrecords' => 'Medical Records',
        ];

        $verbs = [
            'view' => 'View',
            'show' => 'Show',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
        ];

        $now = now();
        $rows = [];

        foreach ($modules as $moduleSlug => $moduleLabel) {
            foreach ($verbs as $verbSlug => $verbLabel) {
                $rows[] = [
                    'task_code' => "{$verbSlug}-{$moduleSlug}",
                    'task_name' => "{$verbLabel}-{$moduleLabel}",
                    'module' => $moduleSlug,
                    'description' => "Allows the role to {$verbLabel} on {$moduleLabel}",
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('tasks')->insert($rows);
    }
}
