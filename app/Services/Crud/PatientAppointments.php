<?php

namespace App\Services\Crud;

use App\CoreService\CoreService;
use App\Models\Appointments;
use App\Services\Crud\Concerns\BuildsListQuery;
use Illuminate\Support\Facades\Auth;

class PatientAppointments extends CoreService
{
    use BuildsListQuery;

    protected function prepare($input)
    {
        if (isset($input['order']) && !in_array($input['order'], ['asc', 'desc'])) {
            unset($input['order']);
        }
        $input['_patient_id'] = Auth::id();
        return $input;
    }

    protected function process($input, $originalData)
    {
        return $this->buildListQuery(
            Appointments::class,
            $input,
            'patient_id',
            [['appointment_date', 'asc'], ['appointment_time', 'asc']],
            function ($query, $input) {
                if (!empty($input['status'])) {
                    $query->where('appointments.status', $input['status']);
                }
                if (!empty($input['appointment_date_from'])) {
                    $query->where('appointments.appointment_date', '>=', $input['appointment_date_from']);
                }
                if (!empty($input['appointment_date_to'])) {
                    $query->where('appointments.appointment_date', '<=', $input['appointment_date_to']);
                }
                if (!empty($input['appointment_date'])) {
                    $query->whereIn('appointments.appointment_date', explode(',', $input['appointment_date']));
                }
            }
        );
    }
}
