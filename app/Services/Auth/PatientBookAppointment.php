<?php

namespace App\Services\Auth;

use App\CoreService\CoreException;
use App\CoreService\CoreService;
use App\Models\Appointments;
use App\Models\Rooms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientBookAppointment extends CoreService
{
    public $transaction = true;

    protected function validation()
    {
        return [
            'doctor_id' => 'required|integer|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepare($input)
    {
        $doctor = \App\Models\Doctors::find($input['doctor_id']);

        if (!$doctor->available) {
            throw new CoreException('Selected doctor is currently unavailable.', 422);
        }

        $room = Rooms::where('available', true)
            ->whereDoesntHave('appointments', function ($q) use ($input) {
                $q->where('appointment_date', $input['appointment_date'])
                  ->where('appointment_time', $input['appointment_time'])
                  ->where('status', '!=', 'cancelled');
            })
            ->first();

        if (!$room) {
            throw new CoreException('No available rooms at this date and time.', 422);
        }

        $conflict = DB::table('appointments')
            ->where('appointment_date', $input['appointment_date'])
            ->where('appointment_time', $input['appointment_time'])
            ->where('doctor_id', $input['doctor_id'])
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($conflict) {
            throw new CoreException('This doctor is already booked at the selected date and time.', 409);
        }

        $this->input = $input;
        $this->input['_room_id'] = $room->id;
        $this->input['_patient_id'] = Auth::id();

        return $input;
    }

    protected function process($input, $originalData)
    {
        $id = DB::table('appointments')->insertGetId([
            'doctor_id' => $input['doctor_id'],
            'patient_id' => $input['_patient_id'],
            'room_id' => $input['_room_id'],
            'appointment_date' => $input['appointment_date'],
            'appointment_time' => $input['appointment_time'],
            'status' => 'scheduled',
            'notes' => $input['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Appointment booked successfully.',
            'data' => ['id' => $id],
        ];
    }
}
