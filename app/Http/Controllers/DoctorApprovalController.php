<?php
// app/Http/Controllers/DoctorApprovalController.php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Notifications\DoctorApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DoctorApprovalController extends Controller
{
    public function pending()
    {
        $pendingDoctors = Doctor::where('is_approved', 0)
            ->with('user')
            ->paginate(10)
            ->through(function ($doctor) {
                return [
                    'doctor_id' => $doctor->id,
                    'name' => $doctor->user->fname . ' ' . $doctor->user->lname,
                    'email' => $doctor->user->email,
                    'phone' => $doctor->user->phone,
                    'specialization' => $doctor->specialization,
                    'email_verified_at' => $doctor->user->email_verified_at,
                    'registered_at' => $doctor->created_at
                ];
            });

        return response()->json([
            'pending_doctors' => $pendingDoctors,
            'count' => $pendingDoctors->count()
        ]);
    }

    public function approve(Doctor $doctor)
    {
        try {
            if ($doctor->is_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor already approved.'
                ], 400);
            }

            if (!$doctor->user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor must verify email first.',
                    'requires_email_verification' => true
                ], 400);
            }

            $user = $doctor->user;
            if (!$user->phone_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor must verify phone first.',
                    'requires_phone_verification' => true
                ], 400);
            }

            $admin = auth()->user();

            DB::beginTransaction();

            $doctor->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]);

            DB::commit();

            // Send email notification
            $emailSent = false;
            $emailError = null;

            try {
                // Log before sending
                Log::info('Attempting to send approval email', [
                    'doctor_id' => $doctor->id,
                    'doctor_email' => $doctor->user->email,
                    'approved_by' => $admin->id
                ]);

                // Send notification
                $doctor->notify(new DoctorApproved($doctor, $admin));

                $emailSent = true;

                Log::info('Doctor approval email sent successfully', [
                    'doctor_id' => $doctor->id,
                    'doctor_email' => $doctor->user->email
                ]);
            } catch (\Exception $e) {
                $emailError = $e->getMessage();
                Log::error('Failed to send doctor approval email', [
                    'doctor_id' => $doctor->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $emailSent
                    ? 'Doctor approved successfully. An email notification has been sent to the doctor.'
                    : 'Doctor approved successfully but email notification failed: ' . $emailError,
                'doctor' => [
                    'id' => $doctor->user->id,
                    'name' => $doctor->user->fname . ' ' . $doctor->user->lname,
                    'email' => $doctor->user->email,
                    'specialization' => $doctor->specialization,
                    'approved_at' => $doctor->approved_at->format('Y-m-d H:i:s'),
                    'approved_by' => $admin->fname . ' ' . $admin->lname,
                    'email_sent' => $emailSent
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Doctor approval failed', [
                'doctor_id' => $doctor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve doctor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $doctors = Doctor::with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doctor) {
                return [
                    'doctor_id' => $doctor->id,
                    'name' => $doctor->user->fname . ' ' . $doctor->user->lname,
                    'email' => $doctor->user->email,
                    'phone' => $doctor->user->phone,
                    'specialization' => $doctor->specialization,
                    'is_approved' => $doctor->is_approved,
                    'approved_at' => $doctor->approved_at,
                    'phone_verified' => !is_null($doctor->user->phone_verified_at),
                    'email_verified' => !is_null($doctor->user->email_verified_at),
                    'registered_at' => $doctor->created_at,
                ];
            });

        return response()->json(['doctors' => $doctors]);
    }

    public function show(Doctor $doctor)
    {
        return response()->json([
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->user->fname . ' ' . $doctor->user->lname,
                'email' => $doctor->user->email,
                'phone' => $doctor->user->phone,
                'national_id' => $doctor->user->national_id,
                'specialization' => $doctor->specialization,
                'is_approved' => $doctor->is_approved,
                'approved_at' => $doctor->approved_at,
                'phone_verified' => !is_null($doctor->user->phone_verified_at),
                'email_verified' => !is_null($doctor->user->email_verified_at),
                'created_at' => $doctor->created_at,
                'updated_at' => $doctor->updated_at,
            ]
        ]);
    }
}
