<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $completedAppointments = DB::table('appointments')
            ->where('status', 'completed')
            ->get();

        if ($completedAppointments->isEmpty()) {
            return;
        }

        $diagnoses = [
            'Hypertension stage 1. Blood pressure 140/90 mmHg. Recommend lifestyle modification and monitor weekly.',
            'Tension headache with moderate severity. Triggered by work stress and lack of sleep. Prescribed analgesics.',
            'Routine immunization completed. Child is healthy and meeting developmental milestones.',
            'Contact dermatitis due to new skincare product. Advised to discontinue and apply topical corticosteroid.',
            'Mild knee osteoarthritis. X-ray shows minor joint space narrowing. Recommend physiotherapy twice weekly.',
            'Intraocular pressure within normal range. Mild myopia progression, updated prescription recommended.',
            'Normal pregnancy progression at 20 weeks. Fetal heartbeat strong. Recommend continued prenatal vitamins.',
            'All blood work within normal ranges. Slightly elevated cholesterol. Dietary consultation recommended.',
            'Generalized anxiety disorder, mild severity. CBT sessions recommended biweekly for 8 weeks.',
            'Mild hearing loss detected in left ear. Audiogram conducted. ENT referral for further evaluation.',
            'Normal EEG results. Migraine management plan adjusted. New preventive medication prescribed.',
        ];

        $treatments = [
            'Prescribed amlodipine 5mg daily. Advised to reduce sodium intake and begin 30-minute daily walks.',
            'Hot compress applied, neck massage performed. Naproxen 250mg prescribed as needed.',
            'Pentavalent vaccine administered. No adverse reaction observed during 15-min monitoring period.',
            'Hydrocortisone cream 1% applied. Advised to use mild, fragrance-free soap for 14 days.',
            'Ice pack applied to knee. Referred to Dr. Eko Prasetyo for physiotherapy program.',
            'Eye drops for lubrication prescribed. Advised to take screen breaks every 20 minutes.',
            'Ultrasound performed — normal anatomy. Iron supplement added to daily vitamins.',
            'Atorvastatin 10mg prescribed. Referred to nutritionist for meal planning.',
            'Discussed stress management techniques. Breathing exercises demonstrated. Follow-up in 2 weeks.',
            'Earwax removal performed by irrigation. Audiogram scheduled for reassessment in 3 months.',
            'Topiramate 25mg at bedtime prescribed. Journaling of migraine triggers recommended.',
        ];

        $prescriptions = [
            'Amlodipine 5mg — 1 tablet daily after breakfast × 30 days',
            'Naproxen 250mg — 1 tablet if pain recurs, max 3 per day × 10 tablets',
            'Paracetamol syrup 120mg/5ml — 5ml every 6 hours if fever (SOS)',
            'Hydrocortisone cream 1% — apply thin layer 2× daily × 14 days',
            'Ibuprofen 400mg — 1 tablet 3× daily after meals × 7 days',
            'Artificial tears eye drops — 1 drop 4× daily × 30 days',
            'Folic acid 400mcg + Ferrous sulfate 65mg — 1 tablet daily × 90 days',
            'Atorvastatin 10mg — 1 tablet at bedtime × 30 days',
            'Sertraline 50mg — 1 tablet daily after breakfast × 30 days',
            'Amoxicillin 500mg — 1 capsule 3× daily × 7 days',
            'Topiramate 25mg — 1 tablet at bedtime × 30 days',
        ];

        $rows = [];
        foreach ($completedAppointments as $appointment) {
            $idx = ($appointment->id - 1) % count($diagnoses);

            $rows[] = [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'diagnosis' => $diagnoses[$idx],
                'treatment' => $treatments[$idx],
                'prescription' => $prescriptions[$idx],
                'notes' => null,
                'created_at' => $now->copy()->addSeconds($idx),
                'updated_at' => $now->copy()->addSeconds($idx),
            ];
        }

        DB::table('medicalrecords')->insert($rows);
    }
}
