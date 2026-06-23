<?php

namespace Database\Seeders;

use App\Models\Roles;
use App\Models\Users;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminId = DB::table('roles')->insertGetId([
            'role_code' => 'super-admin',
            'role_name' => 'Super Admin',
            'description' => 'Full access to all features.',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'fullname' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@clinic.test',
            'password' => Hash::make('Clinic~2026'),
            'role_id' => $superAdminId,
            'photo' => null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
