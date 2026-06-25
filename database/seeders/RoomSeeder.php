<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('rooms')->insert([
            ['room_code' => 'R-001', 'room_name' => 'General Examination 1', 'capacity' => 1, 'available' => true, 'created_at' => $now, 'updated_at' => $now],
            ['room_code' => 'R-002', 'room_name' => 'General Examination 2', 'capacity' => 1, 'available' => true, 'created_at' => $now, 'updated_at' => $now],
            ['room_code' => 'R-003', 'room_name' => 'Pediatric Room', 'capacity' => 1, 'available' => true, 'created_at' => $now, 'updated_at' => $now],
            ['room_code' => 'R-004', 'room_name' => 'Surgical Suite', 'capacity' => 2, 'available' => true, 'created_at' => $now, 'updated_at' => $now],
            ['room_code' => 'R-005', 'room_name' => 'Recovery Room', 'capacity' => 3, 'available' => true, 'created_at' => $now, 'updated_at' => $now],
            ['room_code' => 'R-006', 'room_name' => 'Consultation Room', 'capacity' => 1, 'available' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
