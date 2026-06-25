<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('Patient~2026');

        $patients = [
            ['fullname' => 'Ahmad Fauzi', 'email' => 'patient1@clinic.test', 'phone' => '0856-1111-0001', 'birthdate' => '1985-03-12', 'gender' => 'male', 'address' => 'Jl. Merdeka No. 10, Jakarta'],
            ['fullname' => 'Sari Widyaningsih', 'email' => 'patient2@clinic.test', 'phone' => '0856-1111-0002', 'birthdate' => '1990-07-25', 'gender' => 'female', 'address' => 'Jl. Sudirman No. 45, Bandung'],
            ['fullname' => 'Rudi Hermawan', 'email' => 'patient3@clinic.test', 'phone' => '0856-1111-0003', 'birthdate' => '1978-11-03', 'gender' => 'male', 'address' => 'Jl. Gatot Subroto No. 88, Surabaya'],
            ['fullname' => 'Dewi Larasati', 'email' => 'patient4@clinic.test', 'phone' => '0856-1111-0004', 'birthdate' => '1992-01-18', 'gender' => 'female', 'address' => 'Jl. Diponegoro No. 12, Yogyakarta'],
            ['fullname' => 'Bambang Setiawan', 'email' => 'patient5@clinic.test', 'phone' => '0856-1111-0005', 'birthdate' => '1965-09-30', 'gender' => 'male', 'address' => 'Jl. Thamrin No. 33, Medan'],
            ['fullname' => 'Rina Amelia', 'email' => 'patient6@clinic.test', 'phone' => '0856-1111-0006', 'birthdate' => '1995-05-20', 'gender' => 'female', 'address' => 'Jl. Hayam Wuruk No. 77, Semarang'],
            ['fullname' => 'Hendra Gunawan', 'email' => 'patient7@clinic.test', 'phone' => '0856-1111-0007', 'birthdate' => '1988-12-14', 'gender' => 'male', 'address' => 'Jl. Malioboro No. 5, Yogyakarta'],
            ['fullname' => 'Maya Kartika', 'email' => 'patient8@clinic.test', 'phone' => '0856-1111-0008', 'birthdate' => '2000-02-28', 'gender' => 'female', 'address' => 'Jl. Urip Sumoharjo No. 21, Makassar'],
            ['fullname' => 'Tono Wijaya', 'email' => 'patient9@clinic.test', 'phone' => '0856-1111-0009', 'birthdate' => '1972-08-07', 'gender' => 'male', 'address' => 'Jl. Pahlawan No. 99, Palembang'],
            ['fullname' => 'Ratna Kusuma', 'email' => 'patient10@clinic.test', 'phone' => '0856-1111-0010', 'birthdate' => '1983-04-15', 'gender' => 'female', 'address' => 'Jl. Imam Bonjol No. 44, Denpasar'],
            ['fullname' => 'Arif Rachman', 'email' => 'patient11@clinic.test', 'phone' => '0856-1111-0011', 'birthdate' => '1997-06-22', 'gender' => 'male', 'address' => 'Jl. Pemuda No. 60, Jakarta'],
            ['fullname' => 'Putri Anggraini', 'email' => 'patient12@clinic.test', 'phone' => '0856-1111-0012', 'birthdate' => '1993-10-08', 'gender' => 'female', 'address' => 'Jl. Ahmad Yani No. 17, Bandung'],
            ['fullname' => 'Denny Saputra', 'email' => 'patient13@clinic.test', 'phone' => '0856-1111-0013', 'birthdate' => '1975-01-29', 'gender' => 'male', 'address' => 'Jl. Basuki Rahmat No. 55, Surabaya'],
            ['fullname' => 'Linda Permata', 'email' => 'patient14@clinic.test', 'phone' => '0856-1111-0014', 'birthdate' => '1998-09-11', 'gender' => 'female', 'address' => 'Jl. Setiabudi No. 36, Medan'],
            ['fullname' => 'Sugeng Riyadi', 'email' => 'patient15@clinic.test', 'phone' => '0856-1111-0015', 'birthdate' => '1970-05-03', 'gender' => 'male', 'address' => 'Jl. Gajah Mada No. 81, Semarang'],
            ['fullname' => 'Nur Hasanah', 'email' => 'patient16@clinic.test', 'phone' => '0856-1111-0016', 'birthdate' => '1991-12-19', 'gender' => 'female', 'address' => 'Jl. Veteran No. 23, Malang'],
            ['fullname' => 'Fajar Kurniawan', 'email' => 'patient17@clinic.test', 'phone' => '0856-1111-0017', 'birthdate' => '1986-08-14', 'gender' => 'male', 'address' => 'Jl. Panjaitan No. 74, Jakarta'],
            ['fullname' => 'Anita Susanti', 'email' => 'patient18@clinic.test', 'phone' => '0856-1111-0018', 'birthdate' => '1994-03-27', 'gender' => 'female', 'address' => 'Jl. Cikini Raya No. 9, Jakarta'],
            ['fullname' => 'Wahyu Prasetya', 'email' => 'patient19@clinic.test', 'phone' => '0856-1111-0019', 'birthdate' => '1968-11-05', 'gender' => 'male', 'address' => 'Jl. Kebon Sirih No. 42, Bandung'],
            ['fullname' => 'Reni Oktaviani', 'email' => 'patient20@clinic.test', 'phone' => '0856-1111-0020', 'birthdate' => '2002-07-08', 'gender' => 'female', 'address' => 'Jl. Wijaya Kusuma No. 15, Surabaya'],
            ['fullname' => 'Heri Susanto', 'email' => 'patient21@clinic.test', 'phone' => '0856-1111-0021', 'birthdate' => '1980-02-14', 'gender' => 'male', 'address' => 'Jl. Kertanegara No. 66, Yogyakarta'],
            ['fullname' => 'Yuni Astuti', 'email' => 'patient22@clinic.test', 'phone' => '0856-1111-0022', 'birthdate' => '1999-06-30', 'gender' => 'female', 'address' => 'Jl. Hasanudin No. 3, Medan'],
            ['fullname' => 'Irfan Maulana', 'email' => 'patient23@clinic.test', 'phone' => '0856-1111-0023', 'birthdate' => '1963-10-22', 'gender' => 'male', 'address' => 'Jl. Sutomo No. 48, Palembang'],
            ['fullname' => 'Sri Wahyuni', 'email' => 'patient24@clinic.test', 'phone' => '0856-1111-0024', 'birthdate' => '1996-04-05', 'gender' => 'female', 'address' => 'Jl. Antasari No. 71, Makassar'],
            ['fullname' => 'Bagus Prakoso', 'email' => 'patient25@clinic.test', 'phone' => '0856-1111-0025', 'birthdate' => '1989-01-17', 'gender' => 'male', 'address' => 'Jl. Slamet Riyadi No. 29, Solo'],
        ];

        foreach ($patients as &$patient) {
            $patient['password'] = $password;
            $patient['photo'] = null;
            $patient['created_at'] = $now;
            $patient['updated_at'] = $now;
        }

        DB::table('patients')->insert($patients);
    }
}
