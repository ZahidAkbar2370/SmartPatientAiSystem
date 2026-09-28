<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('patients.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::get('/patients/create/manual', [PatientController::class, 'createManual'])->name('patients.create.manual');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');

    Route::get('/patients/scan', [PatientController::class, 'scan'])->name('patients.scan');
    Route::post('/patients/scan/process', [PatientController::class, 'processScan'])->name('patients.scan.process');
    Route::get('/patients/temp-document', [PatientController::class, 'tempDocument'])->name('patients.temp-document');

    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
    Route::get('/patients/{patient}/document', [PatientController::class, 'document'])->name('patients.document');
});
