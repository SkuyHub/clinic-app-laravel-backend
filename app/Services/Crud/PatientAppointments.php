<?php

namespace App\Services\Crud;

use App\CoreService\CoreService;
use App\Models\Appointments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientAppointments extends CoreService
{
    protected function prepare($input)
    {
        $input['_patient_id'] = Auth::id();
        return $input;
    }

    protected function process($input, $originalData)
    {
        $table = Appointments::TABLE;
        $page = (int) ($input['page'] ?? 1);
        $limit = (int) ($input['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $query = DB::table($table)
            ->select(array_map(fn($f) => "{$table}.{$f}", Appointments::FIELD_LIST))
            ->where("{$table}.patient_id", $input['_patient_id']);

        foreach (Appointments::FIELD_RELATION as $field => $relation) {
            $query->leftJoin(
                "{$relation['linkTable']} as {$relation['aliasTable']}",
                "{$table}.{$field}",
                '=',
                "{$relation['aliasTable']}.{$relation['linkField']}"
            );

            $query->selectRaw("CONCAT_WS(' ', " . implode(', ', array_map(
                fn($f) => "{$relation['aliasTable']}.{$f}",
                $relation['selectFields']
            )) . ") as {$relation['displayName']}");
        }

        if (!empty($input['status'])) {
            $query->where("{$table}.status", $input['status']);
        }

        $total = $query->count();

        $query->orderBy("{$table}.appointment_date", 'asc')
              ->orderBy("{$table}.appointment_time", 'asc');

        $rows = $query->offset($offset)->limit($limit)->get();

        return [
            'success' => true,
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'last_page' => (int) ceil($total / max($limit, 1)),
        ];
    }
}