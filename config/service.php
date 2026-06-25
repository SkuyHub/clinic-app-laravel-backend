<?php

return [
    'services' => [
        [
            'name' => 'DoLogin',
            'class' => \App\Services\Auth\DoUnifiedLogin::class,
            'type' => 'post',
            'end_point' => '/login',
            'guard' => null,
        ],
        [
            'name' => 'Me',
            'class' => \App\Services\Auth\Me::class,
            'type' => 'get',
            'end_point' => '/me',
            'guard' => 'api',
        ],
        [
            'name' => 'DoLogout',
            'class' => \App\Services\Auth\DoLogout::class,
            'type' => 'post',
            'end_point' => '/logout',
            'guard' => 'api',
        ],
        [
            'name' => 'DoLoginDoctor',
            'class' => \App\Services\Auth\DoLoginDoctor::class,
            'type' => 'post',
            'end_point' => '/doctor/login',
            'guard' => null,
        ],
        [
            'name' => 'UpdateDoctorProfile',
            'class' => \App\Services\Auth\UpdateDoctorProfile::class,
            'type' => 'put',
            'end_point' => '/doctor/profile',
            'guard' => 'doctor',
        ],
        [
            'name' => 'DoLoginPatient',
            'class' => \App\Services\Auth\DoLoginPatient::class,
            'type' => 'post',
            'end_point' => '/patient/login',
            'guard' => null,
        ],
        [
            'name' => 'UpdatePatientProfile',
            'class' => \App\Services\Auth\UpdatePatientProfile::class,
            'type' => 'put',
            'end_point' => '/patient/profile',
            'guard' => 'patient',
        ],
        [
            'name' => 'DoctorAppointments',
            'class' => \App\Services\Crud\DoctorAppointments::class,
            'type' => 'get',
            'end_point' => '/doctor/appointments',
            'guard' => 'doctor',
        ],
        [
            'name' => 'DoctorMedicalRecords',
            'class' => \App\Services\Crud\DoctorMedicalRecords::class,
            'type' => 'get',
            'end_point' => '/doctor/medicalrecords',
            'guard' => 'doctor',
        ],
        [
            'name' => 'PatientAppointments',
            'class' => \App\Services\Crud\PatientAppointments::class,
            'type' => 'get',
            'end_point' => '/patient/appointments',
            'guard' => 'patient',
        ],
        [
            'name' => 'PatientMedicalRecords',
            'class' => \App\Services\Crud\PatientMedicalRecords::class,
            'type' => 'get',
            'end_point' => '/patient/medicalrecords',
            'guard' => 'patient',
        ],
    ],
];