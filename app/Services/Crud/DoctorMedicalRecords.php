<?php

namespace App\Services\Crud;

use App\CoreService\CoreService;
use App\Models\MedicalRecords;
use App\Services\Crud\Concerns\BuildsListQuery;
use Illuminate\Support\Facades\Auth;

class DoctorMedicalRecords extends CoreService
{
    use BuildsListQuery;

    protected function prepare($input)
    {
        if (isset($input['order']) && !in_array($input['order'], ['asc', 'desc'])) {
            unset($input['order']);
        }
        $input['_doctor_id'] = Auth::id();
        return $input;
    }

    protected function process($input, $originalData)
    {
        return $this->buildListQuery(
            MedicalRecords::class,
            $input,
            'doctor_id',
            [['created_at', 'desc']],
            function ($query, $input) {
                if (!empty($input['patient_id'])) {
                    $query->where('medicalrecords.patient_id', $input['patient_id']);
                }
            }
        );
    }
}
