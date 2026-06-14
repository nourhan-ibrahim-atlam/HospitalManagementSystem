<?php

use App\Http\Controllers\UpdateRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorApprovalController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\EmailVerification;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Notifications\TestEmail;

Route::prefix('auth')->group(function () {
    Route::post('/register/patient', [AuthController::class, 'registerPatient']);
    Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
    Route::post('/login', [AuthController::class, 'login']);

       Route::prefix('forgot-password')->group(function () {
        Route::post('/send-code', [AuthController::class, 'sendResetCode']);
        Route::post('/verify-code', [AuthController::class, 'verifyResetCode']);
        Route::post('/reset', [AuthController::class, 'resetPassword']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

    });
    Route::post('/send-email-verification', [AuthController::class, 'sendEmailVerification']);
    Route::get('/verify-email/{id}/{hash}', [EmailVerification::class, 'verify'])
        ->name('verification.verify');

    Route::post('/resend-email', [EmailVerification::class, 'resend'])
        ->name('verification.resend');
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me'])
            ->name('user.me');
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/patients/by-fingerprint', [PatientController::class, 'getPatientByFingerprint']);
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{patient}', [PatientController::class, 'show']);
    Route::post('/patients', [PatientController::class, 'create']);
    Route::put('/patients/{patient}', [PatientController::class, 'update']);
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy']);
    Route::post('/patients/fingerprint', [PatientController::class, 'addFingerprint']);

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::post('/doctors', [DoctorController::class, 'store']);
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);
    Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy']);
    Route::put('/doctors/{doctor}', [DoctorController::class, 'update']);
    Route::get('/my-doctor', function () {
        return auth()->user()->doctor?->load('user');
    });
});


Route::middleware(['auth:sanctum', 'check.admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/doctors/pending', [DoctorApprovalController::class, 'pending']);
        Route::get('/doctors', [DoctorApprovalController::class, 'index']);
        Route::get('/doctors/{doctor}', [DoctorApprovalController::class, 'show']);
        Route::post('/doctors/{doctor}/approve', [DoctorApprovalController::class, 'approve']);
        Route::post('/doctors/{doctor}/reject', [DoctorController::class, 'reject']);
    });

Route::prefix('medical-histories')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [MedicalHistoryController::class, 'index']);
    Route::post('/', [MedicalHistoryController::class, 'store']);
    Route::get('/{id}', [MedicalHistoryController::class, 'show']);
    Route::put('/{id}', [MedicalHistoryController::class, 'update']);
    Route::delete('/{id}', [MedicalHistoryController::class, 'destroy']);
});


Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('update-requests')->group(function () {

        Route::get('/', [UpdateRequestController::class, 'index']);
        Route::get('/my-requests', [UpdateRequestController::class, 'myRequests']);
        Route::get('/pending', [UpdateRequestController::class, 'pendingRequests']);
        Route::get('/{updateRequest}', [UpdateRequestController::class, 'show']);

        Route::post('/patient', [UpdateRequestController::class, 'requestPatientUpdate']);
        Route::post('/doctor', [UpdateRequestController::class, 'requestDoctorUpdate']);

        Route::post('/{updateRequest}/approve', [UpdateRequestController::class, 'approve']);
        Route::post('/{updateRequest}/reject', [UpdateRequestController::class, 'reject']);

        Route::post('/{updateRequest}/cancel', [UpdateRequestController::class, 'cancel']);
    });
});

Route::get('/test-email', function () {
    $user = \App\Models\User::first();

    if (!$user) {

        $user = \App\Models\User::factory()->create([
            'email' => 'alqys281.fare@gmail.com',
            'password' => 'password',
            'national_id' => '1234567890',
            'fname' => 'Test',
            'lname' => 'User',
            'phone' => '1234567890',
            'role' => 'patient',
        ]);
    }

    try {
        $user->notify(new TestEmail());
        return response()->json(['message' => 'Test email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to send test email: ' . $e->getMessage()], 500);
    }
});