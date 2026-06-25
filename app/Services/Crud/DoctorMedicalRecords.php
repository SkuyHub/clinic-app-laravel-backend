<?php

namespace App\Services\Crud;

use App\CoreService\CoreService;
use App\Models\MedicalRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorMedicalRecords extends CoreService
{
    protected function prepare($input)
    {
        $input['_doctor_id'] = Auth::id();
        return $input;
    }

    protected function process($input, $originalData)
    {
        $table = MedicalRecords::TABLE;
        $page = (int) ($input['page'] ?? 1);
        $limit = (int) ($input['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $query = DB::table($table)
            ->select(array_map(fn($f) => "{$table}.{$f}", MedicalRecords::FIELD_LIST))
            ->where("{$table}.doctor_id", $input['_doctor_id']);

        foreach (MedicalRecords::FIELD_RELATION as $field => $relation) {
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

        if (!empty($input['search']) && !empty(MedicalRecords::FIELD_SEARCHABLE)) {
            $search = $input['search'];
            $query->where(function ($q) use ($table, $search) {
                foreach (MedicalRecords::FIELD_SEARCHABLE as $field) {
                    $q->orWhereRaw("LOWER({$table}.{$field}) LIKE ?", ['%' . strtolower($search) . '%']);
                }
            });
        }

        if (!empty($input['patient_id'])) {
            $query->where("{$table}.patient_id", $input['patient_id']);
        }

        $total = $query->count();

        $query->orderBy("{$table}.created_at", 'desc');

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