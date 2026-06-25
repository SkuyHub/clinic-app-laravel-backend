<?php

namespace App\Models;

use App\CoreService\CoreException;
use Illuminate\Support\Facades\DB;

class Appointments extends BaseModel
{
    public const TABLE = 'appointments';
    public const FIELD_LIST = ['id', 'doctor_id', 'patient_id', 'room_id', 'appointment_date', 'appointment_time', 'status', 'notes', 'created_at', 'updated_at'];
    public const FIELD_ADD = ['doctor_id', 'patient_id', 'room_id', 'appointment_date', 'appointment_time', 'status', 'notes'];
    public const FIELD_EDIT = ['doctor_id', 'patient_id', 'room_id', 'appointment_date', 'appointment_time', 'status', 'notes'];
    public const FIELD_VIEW = ['id', 'doctor_id', 'patient_id', 'room_id', 'appointment_date', 'appointment_time', 'status', 'notes', 'created_at', 'updated_at'];
    public const FIELD_FILTERABLE = [
        'doctor_id' => ['operator' => '='],
        'patient_id' => ['operator' => '='],
        'room_id' => ['operator' => '='],
        'status' => ['operator' => '='],
        'appointment_date' => ['operator' => '='],
    ];
    public const FIELD_SEARCHABLE = ['notes'];
    public const FIELD_SORTABLE = ['id', 'appointment_date', 'appointment_time', 'created_at', 'updated_at'];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'doctor_id' => 'integer',
        'patient_id' => 'integer',
        'room_id' => 'integer',
        'appointment_date' => 'date',
        'appointment_time' => 'string',
        'status' => 'string',
        'notes' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public const FIELD_RELATION = [
        'doctor_id' => [
            'linkTable' => 'doctors',
            'linkField' => 'id',
            'aliasTable' => 'doctors_ref',
            'displayName' => 'rel_doctor_id',
            'selectFields' => ['fullname', 'specialization'],
        ],
        'patient_id' => [
            'linkTable' => 'patients',
            'linkField' => 'id',
            'aliasTable' => 'patients_ref',
            'displayName' => 'rel_patient_id',
            'selectFields' => ['fullname'],
        ],
        'room_id' => [
            'linkTable' => 'rooms',
            'linkField' => 'id',
            'aliasTable' => 'rooms_ref',
            'displayName' => 'rel_room_id',
            'selectFields' => ['room_code', 'room_name'],
        ],
    ];
    public const FIELD_VALIDATION = [
        'doctor_id' => 'required|integer|exists:doctors,id',
        'patient_id' => 'required|integer|exists:patients,id',
        'room_id' => 'required|integer|exists:rooms,id',
        'appointment_date' => 'required|date',
        'appointment_time' => 'required',
        'status' => 'nullable|in:scheduled,completed,cancelled',
        'notes' => 'nullable|string',
    ];
    public const FIELD_UNIQUE = [];
    public const FIELD_DEFAULT_VALUE = [
        'status' => 'scheduled',
    ];

    protected $table = self::TABLE;

    public static function beforeInsert(array $input): array
    {
        $conflict = DB::table('appointments')
            ->where('appointment_date', $input['appointment_date'])
            ->where('appointment_time', $input['appointment_time'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($input) {
                $q->where('doctor_id', $input['doctor_id'])
                  ->orWhere('room_id', $input['room_id']);
            })
            ->exists();

        if ($conflict) {
            throw new CoreException(
                'This doctor or room is already booked at the selected date and time.',
                409
            );
        }

        $doctorAvailable = Doctors::where('id', $input['doctor_id'])->value('available');
        if (!$doctorAvailable) {
            throw new CoreException('Selected doctor is currently unavailable.', 422);
        }

        if (!array_key_exists('status', $input) || blank($input['status'] ?? null)) {
            $input['status'] = 'scheduled';
        }

        return $input;
    }

    public static function beforeUpdate(array $input): array
    {
        $checkingFields = ['doctor_id', 'patient_id', 'room_id', 'appointment_date', 'appointment_time', 'status'];
        $isRescheduling = !empty(array_intersect($checkingFields, array_keys($input)));

        if ($isRescheduling && (!isset($input['status']) || $input['status'] !== 'cancelled')) {
            $current = DB::table('appointments')->where('id', $input['id'])->first();

            if (!$current) {
                throw new CoreException('Appointment not found.', 404);
            }

            if ($current->status === 'completed') {
                throw new CoreException('Completed appointments cannot be modified.', 422);
            }

            $doctorId = $input['doctor_id'] ?? $current->doctor_id;
            $roomId = $input['room_id'] ?? $current->room_id;
            $date = $input['appointment_date'] ?? $current->appointment_date;
            $time = $input['appointment_time'] ?? $current->appointment_time;

            $conflict = DB::table('appointments')
                ->where('id', '!=', $input['id'])
                ->where('appointment_date', $date)
                ->where('appointment_time', $time)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($doctorId, $roomId) {
                    $q->where('doctor_id', $doctorId)
                      ->orWhere('room_id', $roomId);
                })
                ->exists();

            if ($conflict) {
                throw new CoreException(
                    'This doctor or room is already booked at the selected date and time.',
                    409
                );
            }

            if (array_key_exists('doctor_id', $input)) {
                $doctorAvailable = Doctors::where('id', $doctorId)->value('available');
                if (!$doctorAvailable) {
                    throw new CoreException('Selected doctor is currently unavailable.', 422);
                }
            }
        }

        return $input;
    }
}