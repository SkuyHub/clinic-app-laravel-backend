<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateDoctorMedicalRecord extends CoreService
{
    public $transaction = true;

    protected function validation()
    {
        return [
            'appointment_id' => 'required|integer|exists:appointments,id',
            'diagnosis' => 'required|string',
            'treatment' => 'nullable|string',
            'prescription' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepare($input)
    {
        $appointment = DB::table('appointments')->where('id', $input['appointment_id'])->first();

        if (!$appointment) {
            throw new CoreException('Appointment not found.', 404);
        }

        if ((int) $appointment->doctor_id !== Auth::id()) {
            throw new CoreException('This appointment does not belong to you.', 403);
        }

        $existing = DB::table('medicalrecords')
            ->where('appointment_id', $input['appointment_id'])
            ->exists();

        if ($existing) {
            throw new CoreException('A medical record already exists for this appointment.', 422);
        }

        $input['_doctor_id'] = Auth::id();
        $input['_patient_id'] = $appointment->patient_id;

        return $input;
    }

    protected function process($input, $originalData)
    {
        $id = DB::table('medicalrecords')->insertGetId([
            'appointment_id' => $input['appointment_id'],
            'doctor_id' => $input['_doctor_id'],
            'patient_id' => $input['_patient_id'],
            'diagnosis' => $input['diagnosis'],
            'treatment' => $input['treatment'] ?? null,
            'prescription' => $input['prescription'] ?? null,
            'notes' => $input['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('appointments')
            ->where('id', $input['appointment_id'])
            ->update(['status' => 'completed', 'updated_at' => now()]);

        return [
            'success' => true,
            'message' => 'Medical record created.',
            'data' => ['id' => $id],
        ];
    }
}
