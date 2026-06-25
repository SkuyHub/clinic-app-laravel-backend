<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $doctorIds = DB::table('doctors')->pluck('id')->toArray();
        $patientIds = DB::table('patients')->pluck('id')->toArray();
        $roomIds = DB::table('rooms')->pluck('id')->toArray();

        if (empty($doctorIds) || empty($patientIds) || empty($roomIds)) {
            return;
        }

        $today = $now->copy()->startOfDay();

        $appointments = [
            ['doctor' => 0, 'patient' => 0, 'room' => 2, 'date' => -10, 'time' => '08:00', 'status' => 'completed', 'notes' => 'Routine check-up, patient stable.'],
            ['doctor' => 1, 'patient' => 1, 'room' => 3, 'date' => -10, 'time' => '09:00', 'status' => 'completed', 'notes' => 'Follow-up on previous migraine treatment.'],
            ['doctor' => 2, 'patient' => 2, 'room' => 0, 'date' => -9, 'time' => '08:30', 'status' => 'completed', 'notes' => 'Child annual immunization.'],
            ['doctor' => 3, 'patient' => 3, 'room' => 4, 'date' => -9, 'time' => '10:00', 'status' => 'completed', 'notes' => 'Skin allergy consultation.'],
            ['doctor' => 4, 'patient' => 4, 'room' => 1, 'date' => -8, 'time' => '09:00', 'status' => 'completed', 'notes' => 'Knee pain follow-up.'],
            ['doctor' => 5, 'patient' => 5, 'room' => 5, 'date' => -7, 'time' => '08:00', 'status' => 'completed', 'notes' => 'Eye pressure and vision test.'],
            ['doctor' => 6, 'patient' => 6, 'room' => 2, 'date' => -6, 'time' => '11:00', 'status' => 'completed', 'notes' => 'Pregnancy check-up, second trimester.'],
            ['doctor' => 7, 'patient' => 7, 'room' => 0, 'date' => -5, 'time' => '13:00', 'status' => 'completed', 'notes' => 'General health screening.'],
            ['doctor' => 8, 'patient' => 8, 'room' => 3, 'date' => -4, 'time' => '14:00', 'status' => 'completed', 'notes' => 'Anxiety management consultation.'],
            ['doctor' => 9, 'patient' => 9, 'room' => 5, 'date' => -3, 'time' => '08:30', 'status' => 'completed', 'notes' => 'Hearing test follow-up.'],
            ['doctor' => 0, 'patient' => 10, 'room' => 1, 'date' => -2, 'time' => '09:30', 'status' => 'cancelled', 'notes' => 'Patient rescheduled.'],
            ['doctor' => 1, 'patient' => 11, 'room' => 4, 'date' => -1, 'time' => '10:00', 'status' => 'completed', 'notes' => 'EEG results review.'],
            ['doctor' => 2, 'patient' => 12, 'room' => 5, 'date' => 0, 'time' => '08:00', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 3, 'patient' => 13, 'room' => 0, 'date' => 0, 'time' => '09:00', 'status' => 'scheduled', 'notes' => 'Skin rash evaluation.'],
            ['doctor' => 4, 'patient' => 14, 'room' => 2, 'date' => 0, 'time' => '09:30', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 5, 'patient' => 15, 'room' => 3, 'date' => 1, 'time' => '08:00', 'status' => 'scheduled', 'notes' => 'Annual eye exam.'],
            ['doctor' => 6, 'patient' => 16, 'room' => 1, 'date' => 1, 'time' => '10:30', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 7, 'patient' => 17, 'room' => 4, 'date' => 2, 'time' => '08:30', 'status' => 'cancelled', 'notes' => 'Doctor unavailable.'],
            ['doctor' => 8, 'patient' => 18, 'room' => 5, 'date' => 2, 'time' => '13:30', 'status' => 'scheduled', 'notes' => 'Psychiatric evaluation.'],
            ['doctor' => 9, 'patient' => 19, 'room' => 0, 'date' => 3, 'time' => '09:00', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 0, 'patient' => 20, 'room' => 2, 'date' => 3, 'time' => '11:00', 'status' => 'scheduled', 'notes' => 'ECG follow-up.'],
            ['doctor' => 1, 'patient' => 21, 'room' => 3, 'date' => 4, 'time' => '08:00', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 2, 'patient' => 22, 'room' => 5, 'date' => 4, 'time' => '10:00', 'status' => 'scheduled', 'notes' => 'Child vaccination booster.'],
            ['doctor' => 3, 'patient' => 23, 'room' => 1, 'date' => 5, 'time' => '08:30', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 4, 'patient' => 24, 'room' => 4, 'date' => 5, 'time' => '14:00', 'status' => 'scheduled', 'notes' => 'Post-surgery check-up.'],
            ['doctor' => 5, 'patient' => 0, 'room' => 0, 'date' => 6, 'time' => '09:30', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 6, 'patient' => 1, 'room' => 2, 'date' => 7, 'time' => '08:00', 'status' => 'scheduled', 'notes' => 'IUD insertion.'],
            ['doctor' => 7, 'patient' => 2, 'room' => 3, 'date' => 8, 'time' => '10:30', 'status' => 'cancelled', 'notes' => 'Patient no-show.'],
            ['doctor' => 8, 'patient' => 3, 'room' => 5, 'date' => 9, 'time' => '13:00', 'status' => 'scheduled', 'notes' => 'Therapy session.'],
            ['doctor' => 9, 'patient' => 4, 'room' => 1, 'date' => 10, 'time' => '08:00', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 0, 'patient' => 5, 'room' => 4, 'date' => 11, 'time' => '11:00', 'status' => 'scheduled', 'notes' => null],
            ['doctor' => 1, 'patient' => 6, 'room' => 0, 'date' => 14, 'time' => '08:30', 'status' => 'scheduled', 'notes' => null],
        ];

        $rows = [];
        foreach ($appointments as $a) {
            $appointmentDate = $today->copy()->addDays($a['date'])->format('Y-m-d');

            $rows[] = [
                'doctor_id' => $doctorIds[$a['doctor'] % count($doctorIds)],
                'patient_id' => $patientIds[$a['patient'] % count($patientIds)],
                'room_id' => $roomIds[$a['room'] % count($roomIds)],
                'appointment_date' => $appointmentDate,
                'appointment_time' => $a['time'],
                'status' => $a['status'],
                'notes' => $a['notes'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('appointments')->insert($rows);
    }
}
