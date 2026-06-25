<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('Doctor~2026');

        $doctors = [
            ['fullname' => 'Dr. Anthony Hartono', 'specialization' => 'Cardiology', 'email' => 'dr.hartono@clinic.test', 'phone' => '0812-3456-1001'],
            ['fullname' => 'Dr. Budi Santoso', 'specialization' => 'Neurology', 'email' => 'dr.santoso@clinic.test', 'phone' => '0812-3456-1002'],
            ['fullname' => 'Dr. Citra Dewi', 'specialization' => 'Pediatrics', 'email' => 'dr.dewi@clinic.test', 'phone' => '0812-3456-1003'],
            ['fullname' => 'Dr. Dian Permata', 'specialization' => 'Dermatology', 'email' => 'dr.permata@clinic.test', 'phone' => '0812-3456-1004'],
            ['fullname' => 'Dr. Eko Prasetyo', 'specialization' => 'Orthopedics', 'email' => 'dr.prasetyo@clinic.test', 'phone' => '0812-3456-1005'],
            ['fullname' => 'Dr. Fitri Anggraini', 'specialization' => 'Ophthalmology', 'email' => 'dr.anggraini@clinic.test', 'phone' => '0812-3456-1006'],
            ['fullname' => 'Dr. Gita Nurhayati', 'specialization' => 'Gynecology', 'email' => 'dr.nurhayati@clinic.test', 'phone' => '0812-3456-1007'],
            ['fullname' => 'Dr. Hadi Wibowo', 'specialization' => 'General Practice', 'email' => 'dr.wibowo@clinic.test', 'phone' => '0812-3456-1008'],
            ['fullname' => 'Dr. Indah Lestari', 'specialization' => 'Psychiatry', 'email' => 'dr.lestari@clinic.test', 'phone' => '0812-3456-1009'],
            ['fullname' => 'Dr. Johan Marpaung', 'specialization' => 'ENT', 'email' => 'dr.marpaung@clinic.test', 'phone' => '0812-3456-1010'],
        ];

        foreach ($doctors as &$doctor) {
            $doctor['password'] = $password;
            $doctor['available'] = true;
            $doctor['photo'] = null;
            $doctor['created_at'] = $now;
            $doctor['updated_at'] = $now;
        }

        DB::table('doctors')->insert($doctors);
    }
}
