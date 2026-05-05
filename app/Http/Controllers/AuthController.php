<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterDoctorRequest;
use App\Http\Requests\RegisterPatientRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\TestEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerPatient(RegisterPatientRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            $imagePath = null;
            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('profiles', 'public');
            }
            $user = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'national_id' => $validated['national_id'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'email' => $validated['email'],
                'role' => 'patient',
                'profile_image' => $imagePath,
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $patient = Patient::create([
                'user_id' => $user->id,
                'blood_type' => $validated['blood_type'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
            ]);

            DB::commit();

            event(new Registered($user));


            return response()->json([
                'message' => 'Patient registered successfully',
                'user' => [
                    'id' => $user->id,
                    'fname' => $user->fname,
                    'lname' => $user->lname,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile_image' => $user->profile_image,
                    'gender' => $user->gender,
                    'date_of_birth' => $user->date_of_birth,
                    'address' => $user->address
                ],
                'patient' => [
                    'blood_type' => $patient->blood_type,
                    'emergency_contact' => $patient->emergency_contact,
                ],
                'requires_phone_verification' => true,
                'requires_email_verification' => true,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerDoctor(RegisterDoctorRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $profileImagePath = null;
            if ($request->hasFile('profile_image')) {
                $profileImagePath = $request->file('profile_image')->store('profiles', 'public');
            }

            $medicalLicensePath = null;
            $degreeCertificatePath = null;
            $professionalIdCardPath = null;

            if ($request->hasFile('medical_license')) {
                $medicalLicensePath = $request->file('medical_license')->store('doctor-documents/licenses', 'public');
            }

            if ($request->hasFile('degree_certificate')) {
                $degreeCertificatePath = $request->file('degree_certificate')->store('doctor-documents/degrees', 'public');
            }

            if ($request->hasFile('professional_id_card')) {
                $professionalIdCardPath = $request->file('professional_id_card')->store('doctor-documents/id-cards', 'public');
            }

            $user = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'national_id' => $validated['national_id'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'doctor',
                'email' => $validated['email'],
                'profile_image' => $profileImagePath,
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'address' => $validated['address'],
            ]);

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization' => $validated['specialization'],
                'medical_license' => $medicalLicensePath,
                'degree_certificate' => $degreeCertificatePath,
                'professional_id_card' => $professionalIdCardPath,
                'is_approved' => false,
            ]);

            DB::commit();

            event(new Registered($user));
            return response()->json([
                'message' => 'Doctor registered successfully. Please verify your phone and email. Your account will be activated after admin approval.',
                'user' => [
                    'id' => $user->id,
                    'fname' => $user->fname,
                    'lname' => $user->lname,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image,
                    'gender' => $user->gender,
                    'date_of_birth' => $user->date_of_birth,
                    'address' => $user->address,
                ],
                'doctor' => [
                    'specialization' => $doctor->specialization,
                    'documents' => [
                        'medical_license' => $doctor->medical_license ? Storage::url($doctor->medical_license) : null,
                        'degree_certificate' => $doctor->degree_certificate ? Storage::url($doctor->degree_certificate) : null,
                        'professional_id_card' => $doctor->professional_id_card ? Storage::url($doctor->professional_id_card) : null,
                    ]
                ],
                'requires_phone_verification' => true,
                'requires_email_verification' => true,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Doctor registration failed: ' . $e->getMessage());
            return response()->json(['message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function sendEmailVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->isEmailVerified()) {
            return response()->json([
                'message' => 'Email already verified.'
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Email verification link sent successfully.',
            'email' => $user->email,
        ]);
    }
    public function login(Request $request)
    {
        $request->validate([
            'national_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('national_id', $request->national_id)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'national_id' => ['The provided credentials are incorrect.'],
            ]);
        }

        $loginStatus = $user->canLogin();

        if (!$loginStatus['can_login']) {
            $missing = [];

            if (isset($loginStatus['requirements']['email_verified']) && !$loginStatus['requirements']['email_verified']) {
                $missing[] = 'email verification';
            }
            if (isset($loginStatus['requirements']['admin_approved']) && !$loginStatus['requirements']['admin_approved']) {
                $missing[] = 'admin approval';
            }

            $responseData = [
                'message' => 'Cannot login. Please complete: ' . implode(', ', $missing) . '.',
                'requirements' => $loginStatus['requirements'],
            ];
            return response()->json($responseData, 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token', [$user->role])->plainTextToken;

        $responseData = [
            'message' => 'Login successful.',
            'user' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'national_id' => $user->national_id,
                'phone' => $user->phone,
                'role' => $user->role,
                'email' => $user->email,
                'profile_image' => $user->profile_image,
                'gender' => $user->gender,
                'date_of_birth' => $user->date_of_birth,
                'address' => $user->address,
                'phone_verified' => $user->isPhoneVerified(),
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ];

        if ($user->isDoctor() && $user->doctor) {
            $responseData['user']['email'] = $user->email;
            $responseData['user']['email_verified'] = $user->isEmailVerified();
            $responseData['user']['is_approved'] = $user->doctor->isApproved();
            $responseData['user']['specialization'] = $user->doctor->specialization;
            $responseData['user']['medical_license'] = $user->doctor->medical_license;
            $responseData['user']['degree_certificate'] = $user->doctor->degree_certificate;
            $responseData['user']['professional_id_card'] = $user->doctor->professional_id_card;
        }

        return response()->json($responseData);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $data = [
            'id' => $user->id,
            'fname' => $user->fname,
            'lname' => $user->lname,
            'national_id' => $user->national_id,
            'phone' => $user->phone,
            'blood_type' => optional($user->patient)->blood_type,
            'emergency_contact' => optional($user->patient)->emergency_contact,
            'role' => $user->role,
            'phone_verified' => $user->isPhoneVerified(),
            'created_at' => $user->created_at,

        ];

        if ($user->isPatient() && $user->patient) {
            $data['profile'] = $user->patient;
        } elseif ($user->isDoctor() && $user->doctor) {
            $data['profile'] = [
                'id' => $user->doctor->id,
                'specialization' => $user->doctor->specialization,
                'email' => $user->doctor->email,
                'email_verified' => $user->isEmailVerified(),
                'is_approved' => $user->doctor->isApproved(),
                'approved_at' => $user->doctor->approved_at,
            ];
        }

        return response()->json($data);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.'
        ]);
    }
}
