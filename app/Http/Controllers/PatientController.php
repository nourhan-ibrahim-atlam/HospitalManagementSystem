<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterPatientRequest;
use App\Models\EmergencyVisit;
use App\Models\FingerPrintSimulation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        $user = $request->user();

        if ($user->role === 'doctor') {
            \Log::info('Doctor user:', ['user_id' => $user->id]);

            if ($user->doctor) {
                \Log::info('Doctor record:', ['doctor_id' => $user->doctor->id]);

                $emergencyVisits = EmergencyVisit::where('doctor_id', $user->doctor->id)->get();
                \Log::info('Emergency visits count:', ['count' => $emergencyVisits->count()]);
            } else {
                \Log::error('Doctor record not found for user: ' . $user->id);
            }
        }

        $query = Patient::with(['user']);

        if ($user->role === 'doctor') {
            $doctorId = $user->doctor->id;
            $query->whereHas('emergencyVisits', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            });
        } elseif ($user->role === 'patient') {
            $query->where('user_id', $user->id);
        }

        $patients = $query->latest()->paginate(15);

        return response()->json($patients);
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);


        $patient->load(['user', 'fingerprint', 'emergencyVisits']);

        return response()->json($patient);
    }
    public function update(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $user = auth()->user();

        if ($user->role === 'admin') {

            $patient->update($request->only([
                'blood_type',
                'emergency_contact',
            ]));

            $patient->user->update($request->only([
                'fname',
                'lname',
                'email',
                'phone',
                'role',
                'password',
                'national_id',
                'profile_image',
                'date_of_birth',
                'address',
                'gender'
            ]));
            $patient->load(['user', 'fingerprint']);
            return response()->json(['message' => 'Admin updated everything', 'data' => $patient]);
        }

        if ($user->role === 'doctor') {
            $patient->update($request->only([
                'blood_type',

            ]));
        }

        if ($user->role === 'patient') {
            $patient->user->update($request->only([
                'phone',
                'fname',
                'lname',
                'email',
                'profile_image',
                'emergency_contact',
            ]));
        }
        $patient->load(['user', 'fingerprint']);
        return response()->json(['message' => 'Updated successfully',  'data' => $patient]);
    }
    public function destroy(Patient $patient)
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return response()->json(['message' => 'Patient deleted successfully']);
    }

    public function addFingerprint(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || $authUser->role !== 'patient') {
            return response()->json([
                'success' => false,
                'message' => 'Only patients can create fingerprints'
            ], 403);
        }

        $patient = $authUser->patient;

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient profile not found'
            ], 404);
        }

        if ($patient->fingerprint) {
            return response()->json([
                'success' => false,
                'message' => 'Fingerprint already exists'
            ], 409);
        }

        $fingerprint = FingerPrintSimulation::create([
            'patient_id' => $patient->id,
            'fingerprint_code' => FingerPrintSimulation::generateCode()
        ]);

        return response()->json([
            'success' => true,
            'data' => $fingerprint
        ]);
    }

    public function create(RegisterPatientRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'fname' => $validated['fname'],
            'lname' => $validated['lname'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'national_id' => $validated['national_id'],
            'password' => Hash::make($validated['password']),
            'role' => 'patient',
            "profile_image" => "images/default-doctor.png",
            "email_verified_at" => now(),
            "gender" => $validated["gender"],
            "date_of_birth" => $validated["date_of_birth"],
            "address" => $validated["address"]
        ]);

        $patient = Patient::create([
            'user_id' => $user->id,
            'blood_type' => $validated['blood_type'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
        ]);

        FingerPrintSimulation::create([
            'patient_id' => $patient->id,
            'fingerprint_code' => Str::random(32)
        ]);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');

            $user->update([
                'profile_image' => $path
            ]);
        }

        $patient->load(['user', 'fingerprint']);

        return response()->json([
            'message' => 'Patient registered successfully',
            'data' => $patient
        ], 201);
    }

    public function getPatientByFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_code' => 'required|string'
        ]);

        $fingerprint = FingerPrintSimulation::where('fingerprint_code', $request->fingerprint_code)
            ->with([
                'patient.user',
                'patient.fingerprint',
                'patient.emergencyVisits' => function ($query) {
                    $query->latest()->with(['doctor.user']);
                },
            ])
            ->first();

        if (!$fingerprint) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found with the provided fingerprint'
            ], 404);
        }

        $patient = $fingerprint->patient;

        $patient->loadCount([
            'emergencyVisits',
        ]);



        $patientData = [

            'patient' => [
                'id' => $patient->id,
                'user_id' => $patient->user_id,
                'blood_type' => $patient->blood_type,
                'emergency_contact' => $patient->emergency_contact,
                'created_at' => $patient->created_at,
                'updated_at' => $patient->updated_at,


                'user' => [
                    'id' => $patient->user->id,
                    'fname' => $patient->user->fname,
                    'lname' => $patient->user->lname,
                    'full_name' => $patient->user->fname . ' ' . $patient->user->lname,
                    'email' => $patient->user->email,
                    'phone' => $patient->user->phone,
                    'date_of_birth' => $patient->user->date_of_birth,
                    'gender' => $patient->user->gender,
                    'address' => $patient->user->address,
                    'profile_image' => $patient->user->profile_image,
                ],

            ],

            'emergency_visits' => $patient->emergencyVisits->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'visit_date' => $visit->visit_date,
                    'chief_complaint' => $visit->chief_complaint,
                    'severity' => $visit->severity,
                    'status' => $visit->status,
                    'diagnosis' => $visit->diagnosis,
                    'treatment' => $visit->treatment,
                    'notes' => $visit->notes,
                    'created_at' => $visit->created_at,
                    'doctor' => $visit->doctor ? [
                        'id' => $visit->doctor->id,
                        'name' => $visit->doctor->user ? $visit->doctor->user->fname . ' ' . $visit->doctor->user->lname : 'N/A',
                        'specialization' => $visit->doctor->specialization,
                        'phone' => $visit->doctor->phone,
                        'profile_image' => $visit->doctor->user ? $visit->doctor->user->profile_image : null,
                    ] : null,
                ];
            }),
        ];
        $authUser = auth()->user();
        if ($authUser) {
            $patientData['authorization'] = [
                'can_edit' => ($authUser->role === 'admin') ||
                    ($authUser->role === 'doctor' && $authUser->doctor) ||
                    ($authUser->role === 'patient' && $patient->user_id === $authUser->id),
                'can_delete' => ($authUser->role === 'admin'),
                'access_level' => $authUser->role,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $patientData,
            'message' => 'Patient found successfully'
        ]);
    }
}
