<?php

namespace App\Models;

use App\CoreService\CoreException;
use Illuminate\Support\Facades\DB;

class MedicalRecords extends BaseModel
{
    public const TABLE = 'medicalrecords';
    public const FIELD_LIST = ['id', 'appointment_id', 'doctor_id', 'patient_id', 'diagnosis', 'treatment', 'prescription', 'notes', 'created_at', 'updated_at'];
    public const FIELD_ADD = ['appointment_id', 'doctor_id', 'patient_id', 'diagnosis', 'treatment', 'prescription', 'notes'];
    public const FIELD_EDIT = ['diagnosis', 'treatment', 'prescription', 'notes'];
    public const FIELD_VIEW = ['id', 'appointment_id', 'doctor_id', 'patient_id', 'diagnosis', 'treatment', 'prescription', 'notes', 'created_at', 'updated_at'];
    public const FIELD_FILTERABLE = [
        'doctor_id' => ['operator' => '='],
        'patient_id' => ['operator' => '='],
        'appointment_id' => ['operator' => '='],
    ];
    public const FIELD_SEARCHABLE = ['diagnosis', 'treatment'];
    public const FIELD_SORTABLE = ['id', 'doctor_id', 'patient_id', 'diagnosis', 'treatment', 'prescription', 'created_at', 'updated_at'];
    public const FIELD_TYPE = [
        'id' => 'integer',
        'appointment_id' => 'integer',
        'doctor_id' => 'integer',
        'patient_id' => 'integer',
        'diagnosis' => 'string',
        'treatment' => 'string',
        'prescription' => 'string',
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
    ];
    public const FIELD_VALIDATION = [
        'appointment_id' => 'required|integer|exists:appointments,id',
        'doctor_id' => 'required|integer|exists:doctors,id',
        'patient_id' => 'required|integer|exists:patients,id',
        'diagnosis' => 'required|string',
        'treatment' => 'nullable|string',
        'prescription' => 'nullable|string',
        'notes' => 'nullable|string',
    ];
    public const FIELD_UNIQUE = [
        ['appointment_id'],
    ];
    public const FIELD_DEFAULT_VALUE = [];

    protected $table = self::TABLE;

    public static function beforeInsert(array $input): array
    {
        $appointment = DB::table('appointments')->where('id', $input['appointment_id'])->first();

        if (!$appointment) {
            throw new CoreException('Appointment not found.', 404);
        }

        if ((int) $appointment->doctor_id !== (int) $input['doctor_id']) {
            throw new CoreException('The doctor does not match the appointment\'s assigned doctor.', 422);
        }

        if ((int) $appointment->patient_id !== (int) $input['patient_id']) {
            throw new CoreException('The patient does not match the appointment\'s assigned patient.', 422);
        }

        return $input;
    }
}