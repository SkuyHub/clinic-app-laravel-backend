<?php

namespace App\Services\Auth;

use App\CoreService\CoreService;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends CoreService
{
    protected function prepare($input)
    {
        return $input;
    }

    protected function process($input, $originalData)
    {
        $totalDoctors = DB::table('doctors')->count();
        $totalPatients = DB::table('patients')->count();
        $totalAppointments = DB::table('appointments')->count();
        $today = now()->toDateString();

        $todaySql = DB::table('appointments')->where('appointment_date', $today);
        $todayCount = $todaySql->count();
        $completedToday = (clone $todaySql)->where('status', 'completed')->count();

        $weekAgo = now()->subDays(7)->toDateString();
        $weeklyTrend = DB::table('appointments')
            ->select(DB::raw('appointment_date, count(*) as count'))
            ->where('appointment_date', '>=', $weekAgo)
            ->groupBy('appointment_date')
            ->orderBy('appointment_date')
            ->get();

        $recent = DB::table('appointments')
            ->join('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->select(
                'appointments.id',
                'appointments.appointment_date',
                'appointments.appointment_time',
                'appointments.status',
                'doctors.fullname as doctor_name',
                'patients.fullname as patient_name',
            )
            ->orderBy('appointments.created_at', 'desc')
            ->limit(5)
            ->get();

        $scheduledCount = DB::table('appointments')->where('status', 'scheduled')->count();
        $cancelledCount = DB::table('appointments')->where('status', 'cancelled')->count();

        return [
            'success' => true,
            'data' => [
                'total_doctors' => $totalDoctors,
                'total_patients' => $totalPatients,
                'total_appointments' => $totalAppointments,
                'today_appointments' => $todayCount,
                'completed_today' => $completedToday,
                'scheduled' => $scheduledCount,
                'cancelled' => $cancelledCount,
                'weekly_trend' => $weeklyTrend,
                'recent_appointments' => $recent,
            ],
        ];
    }
}
