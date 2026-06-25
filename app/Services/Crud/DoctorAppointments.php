<?php

namespace App\Services\Crud;

use App\CoreService\CoreService;
use App\Models\Appointments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorAppointments extends CoreService
{
    protected function prepare($input)
    {
        $input['_doctor_id'] = Auth::id();
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
            ->where("{$table}.doctor_id", $input['_doctor_id']);

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

        if (!empty($input['search']) && !empty(Appointments::FIELD_SEARCHABLE)) {
            $search = $input['search'];
            $query->where(function ($q) use ($table, $search) {
                foreach (Appointments::FIELD_SEARCHABLE as $field) {
                    $q->orWhereRaw("LOWER({$table}.{$field}) LIKE ?", ['%' . strtolower($search) . '%']);
                }
            });
        }

        if (!empty($input['status'])) {
            $query->where("{$table}.status", $input['status']);
        }

        if (!empty($input['appointment_date'])) {
            $query->where("{$table}.appointment_date", $input['appointment_date']);
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