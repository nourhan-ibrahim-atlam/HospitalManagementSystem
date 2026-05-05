<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpParser\Comment\Doc;

class DoctorController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Doctor::class);
        $doctors = Doctor::with('user')->paginate(10);
        return response()->json($doctors);
    }


public function show(Doctor $doctor)
{
        return new DoctorResource($doctor->load('user'));
}
    public function store(RegisterDoctorRequest $request)
    {
        $this->authorize('create', Doctor::class);

        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $authUser = auth()->user();

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
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'national_id' => $validated['national_id'],
                'password' => Hash::make($validated['password']),
                'role' => 'doctor',
                'profile_image' => $profileImagePath,
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);


            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization' => $validated['specialization'],
                'medical_license' => $medicalLicensePath,
                'degree_certificate' => $degreeCertificatePath,
                'professional_id_card' => $professionalIdCardPath,
                'is_approved' => $authUser && $authUser->isAdmin(),
                'approved_at' => $authUser && $authUser->isAdmin() ? now() : null,
                'approved_by' => $authUser && $authUser->isAdmin() ? $authUser->id : null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Doctor created successfully',
                'doctor' => $doctor->load('user'),
                'documents' => [
                    'medical_license' => $medicalLicensePath ? Storage::url($medicalLicensePath) : null,
                    'degree_certificate' => $degreeCertificatePath ? Storage::url($degreeCertificatePath) : null,
                    'professional_id_card' => $professionalIdCardPath ? Storage::url($professionalIdCardPath) : null,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Doctor creation failed: ' . $e->getMessage());

            if (isset($profileImagePath) && Storage::disk('public')->exists($profileImagePath)) {
                Storage::disk('public')->delete($profileImagePath);
            }
            if (isset($medicalLicensePath) && Storage::disk('public')->exists($medicalLicensePath)) {
                Storage::disk('public')->delete($medicalLicensePath);
            }
            if (isset($degreeCertificatePath) && Storage::disk('public')->exists($degreeCertificatePath)) {
                Storage::disk('public')->delete($degreeCertificatePath);
            }
            if (isset($professionalIdCardPath) && Storage::disk('public')->exists($professionalIdCardPath)) {
                Storage::disk('public')->delete($professionalIdCardPath);
            }

            return response()->json([
                'message' => 'Failed to create doctor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Doctor $doctor)
    {
        $this->authorize('update', $doctor);

        try {
            DB::beginTransaction();

            $user = $doctor->user;

            if ($request->has('fname')) {
                $user->fname = $request->fname;
            }
            if ($request->has('lname')) {
                $user->lname = $request->lname;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }
            if ($request->has('national_id')) {
                $user->national_id = $request->national_id;
            }
            if ($request->has('gender')) {
                $user->gender = $request->gender;
            }
            if ($request->has('date_of_birth')) {
                $user->date_of_birth = $request->date_of_birth;
            }
            if ($request->has('address')) {
                $user->address = $request->address;
            }

            if ($request->hasFile('profile_image')) {
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                $user->profile_image = $request->file('profile_image')->store('profiles', 'public');
            }

            $user->save();

            if ($request->has('specialization')) {
                $doctor->specialization = $request->specialization;
            }

            if ($request->hasFile('medical_license')) {

                if ($doctor->medical_license && Storage::disk('public')->exists($doctor->medical_license)) {
                    Storage::disk('public')->delete($doctor->medical_license);
                }
                $doctor->medical_license = $request->file('medical_license')->store('doctor-documents/licenses', 'public');
            }

            if ($request->hasFile('degree_certificate')) {
                if ($doctor->degree_certificate && Storage::disk('public')->exists($doctor->degree_certificate)) {
                    Storage::disk('public')->delete($doctor->degree_certificate);
                }
                $doctor->degree_certificate = $request->file('degree_certificate')->store('doctor-documents/degrees', 'public');
            }

            if ($request->hasFile('professional_id_card')) {
                if ($doctor->professional_id_card && Storage::disk('public')->exists($doctor->professional_id_card)) {
                    Storage::disk('public')->delete($doctor->professional_id_card);
                }
                $doctor->professional_id_card = $request->file('professional_id_card')->store('doctor-documents/id-cards', 'public');
            }

            $doctor->save();

            DB::commit();

            return response()->json([
                'message' => 'Doctor updated successfully',
                'doctor' => $doctor->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Doctor update failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update doctor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(Doctor $doctor, Request $request)
    {
        $this->authorize('approve', $doctor);

        try {
            $doctor->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejection_reason' => null
            ]);

            return response()->json([
                'message' => 'Doctor approved successfully',
                'doctor' => $doctor
            ]);
        } catch (\Exception $e) {
            Log::error('Doctor approval failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to approve doctor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Doctor $doctor, Request $request)
    {
        $this->authorize('approve', $doctor);

        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        try {
            $doctor->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => $request->rejection_reason
            ]);

            return response()->json([
                'message' => 'Doctor rejected',
                'rejection_reason' => $doctor->rejection_reason
            ]);
        } catch (\Exception $e) {
            Log::error('Doctor rejection failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to reject doctor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Doctor $doctor)
    {
        $this->authorize('delete', $doctor);

        try {
            DB::beginTransaction();

            if ($doctor->medical_license && Storage::disk('public')->exists($doctor->medical_license)) {
                Storage::disk('public')->delete($doctor->medical_license);
            }
            if ($doctor->degree_certificate && Storage::disk('public')->exists($doctor->degree_certificate)) {
                Storage::disk('public')->delete($doctor->degree_certificate);
            }
            if ($doctor->professional_id_card && Storage::disk('public')->exists($doctor->professional_id_card)) {
                Storage::disk('public')->delete($doctor->professional_id_card);
            }

            if ($doctor->user->profile_image && Storage::disk('public')->exists($doctor->user->profile_image)) {
                Storage::disk('public')->delete($doctor->user->profile_image);
            }

            $doctor->user()->delete();
            $doctor->delete();

            DB::commit();

            return response()->json([
                'message' => 'Doctor deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Doctor deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete doctor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDocuments(Doctor $doctor)
    {
        $this->authorize('view', $doctor);

        return response()->json([
            'medical_license' => $doctor->medical_license ? Storage::url($doctor->medical_license) : null,
            'degree_certificate' => $doctor->degree_certificate ? Storage::url($doctor->degree_certificate) : null,
            'professional_id_card' => $doctor->professional_id_card ? Storage::url($doctor->professional_id_card) : null,
        ]);
    }
}
