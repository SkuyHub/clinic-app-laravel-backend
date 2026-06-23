<?php

use App\CoreService\CallService;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

foreach (config('service.services', []) as $service) {
    if (empty($service['end_point']) || empty($service['type'])) {
        continue;
    }

    $serviceName = $service['name'];
    $middleware = !empty($service['guard'])
        ? ["setguard:{$service['guard']}", 'auth.rest']
        : [];

    Route::match([$service['type']], $service['end_point'], function () use ($serviceName) {
        return CallService::run($serviceName, request()->all());
    })->middleware($middleware);
}

Route::middleware(['setguard:api', 'auth.rest'])->group(function () {
    Route::get('/{model}/list', [CrudController::class, 'index']);
    Route::get('/{model}/dataset', [CrudController::class, 'dataset']);
    Route::get('/{model}/{id}/show', [CrudController::class, 'show']);
    Route::post('/{model}/create', [CrudController::class, 'create']);
    Route::put('/{model}/update', [CrudController::class, 'update']);
    Route::delete('/{model}/delete', [CrudController::class, 'delete']);
});

Route::middleware(['setguard:doctor', 'auth.rest'])->group(function () {
    Route::get('/doctor/me', function () {
        $doctor = \Illuminate\Support\Facades\Auth::user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctor->id,
                'fullname' => $doctor->fullname,
                'specialization' => $doctor->specialization,
                'email' => $doctor->email,
            ],
        ]);
    });
});

Route::middleware(['setguard:patient', 'auth.rest'])->group(function () {
    Route::get('/patient/me', function () {
        $patient = \Illuminate\Support\Facades\Auth::user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $patient->id,
                'fullname' => $patient->fullname,
                'email' => $patient->email,
            ],
        ]);
    });
});

Route::post('/upload-tmp', [UploadController::class, 'upload']);

Route::get('/file/{path}', function ($path) {
    if (!Storage::disk('document')->exists($path)) {
        abort(404);
    }
    return Storage::disk('document')->response($path);
})->where('path', '.*')->name('file.read');