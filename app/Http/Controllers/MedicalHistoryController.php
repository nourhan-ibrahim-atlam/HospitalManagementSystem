<?php

namespace App\Http\Controllers;

use App\Models\Allergy;
use App\Models\BloodTestParameter;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Immunization;
use App\Models\LabTest;
use App\Models\MedicalHistory;
use App\Models\Prescription;
use App\Models\Surgery;
use App\Models\Treatment;
use App\Models\VitalSign;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MedicalHistoryController extends Controller
{
    public function index(Request $request)
    {
        $patientId = $request->input('patient_id');

        $query = MedicalHistory::with([
            'patient.user',
            'doctor.user',
            'labTests.bloodTestParameters',
            'treatments',
            'prescriptions',
            'diagnoses',
            'vitalSigns'
        ]);

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        $user = Auth::user();

        if ($user->isAdmin()) {
        } elseif ($user->isDoctor() && $user->doctor) {
            $query->where('doctor_id', $user->doctor->id);
        } elseif ($user->isPatient() && $user->patient) {
            $query->where('patient_id', $user->patient->id);
        }

        $medicalHistories = $query->orderBy('visit_date', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $medicalHistories,
            'message' => 'Medical histories retrieved successfully'
        ]);
    }


    public function show($id)
    {
        $medicalHistory = MedicalHistory::with([
            'patient.user',
            'doctor.user',
            'labTests' => function ($query) {
                $query->with(['bloodTestParameters', 'doctor.user'])->latest();
            },
            'treatments' => function ($query) {
                $query->with('doctor.user')->latest();
            },
            'prescriptions' => function ($query) {
                $query->with('doctor.user')->latest();
            },
            'diagnoses' => function ($query) {
                $query->with('doctor.user')->latest();
            },
            'vitalSigns' => function ($query) {
                $query->latest();
            }
        ])->findOrFail($id);

        $patientId = $medicalHistory->patient_id;

        $allergies = Allergy::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        $immunizations = Immunization::where('patient_id', $patientId)
            ->with('doctor.user')
            ->orderBy('administration_date', 'desc')
            ->get();

        $surgeries = Surgery::where('patient_id', $patientId)
            ->orderBy('surgery_date', 'desc')
            ->get();

        $otherLabTests = LabTest::where('patient_id', $patientId)
            ->where(function ($query) use ($id) {
                $query->whereNull('medical_history_id')
                    ->orWhere('medical_history_id', '!=', $id);
            })
            ->with(['bloodTestParameters', 'doctor.user'])
            ->latest()
            ->get();

        $otherTreatments = Treatment::where('patient_id', $patientId)
            ->where(function ($query) use ($id) {
                $query->whereNull('medical_history_id')
                    ->orWhere('medical_history_id', '!=', $id);
            })
            ->with('doctor.user')
            ->latest()
            ->get();

        $otherPrescriptions = Prescription::where('patient_id', $patientId)
            ->where(function ($query) use ($id) {
                $query->whereNull('medical_history_id')
                    ->orWhere('medical_history_id', '!=', $id);
            })
            ->with('doctor.user')
            ->latest()
            ->get();

        $otherDiagnoses = Diagnosis::where('patient_id', $patientId)
            ->where(function ($query) use ($id) {
                $query->whereNull('medical_history_id')
                    ->orWhere('medical_history_id', '!=', $id);
            })
            ->with('doctor.user')
            ->latest()
            ->get();

        $otherVitalSigns = VitalSign::where('patient_id', $patientId)
            ->where(function ($query) use ($id) {
                $query->whereNull('medical_history_id')
                    ->orWhere('medical_history_id', '!=', $id);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'medical_history' => $medicalHistory,
                'allergies' => $allergies,
                'immunizations' => $immunizations,
                'surgeries' => $surgeries,
                'other_records' => [
                    'lab_tests' => $otherLabTests,
                    'treatments' => $otherTreatments,
                    'prescriptions' => $otherPrescriptions,
                    'diagnoses' => $otherDiagnoses,
                    'vital_signs' => $otherVitalSigns
                ]
            ],
            'message' => 'Medical history retrieved successfully'
        ]);
    }
    public function destroy($id)
    {
        $medicalHistory = MedicalHistory::findOrFail($id);


        if (!Auth::user() || Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admin can delete medical history'
            ], 403);
        }

        $medicalHistory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medical history deleted successfully'
        ]);
    }

   public function store(Request $request)
{
    // First, determine the doctor_id from the logged-in user
    $doctorId = null;



    if (Auth::check() && Auth::user()->doctor) {
        $doctorId = Auth::user()->doctor->id;
    }

    $rules = [
        'patient_id' => 'required|exists:patients,id',
        'visit_date' => 'required|date',
        'chief_complaint' => 'required|string',
        'present_illness_history' => 'required|string',
        'past_medical_history' => 'nullable|string',
        'family_history' => 'nullable|string',
        'social_history' => 'nullable|string',
        'allergies_text' => 'nullable|string',
        'current_medications' => 'nullable|string',
        'physical_examination' => 'nullable|string',
        'diagnosis_text' => 'nullable|string',
        'treatment_plan' => 'nullable|string',
        'doctor_notes' => 'nullable|string',
        'visit_type' => 'required|in:initial,follow_up,emergency,consultation',
        'status' => 'sometimes|in:active,resolved,inactive',

        // Lab Tests
        'lab_tests' => 'nullable|array',
        'lab_tests.*.test_name' => 'required_with:lab_tests|string',
        'lab_tests.*.test_category' => 'required_with:lab_tests|string',
        'lab_tests.*.test_date' => 'required_with:lab_tests|date',
        'lab_tests.*.results' => 'nullable|string',
        'lab_tests.*.reference_range' => 'nullable|string',
        'lab_tests.*.interpretation' => 'nullable|string',
        'lab_tests.*.status' => 'nullable|in:pending,in_progress,completed,cancelled',

        // Blood Test Parameters
        'lab_tests.*.parameters' => 'nullable|array',
        'lab_tests.*.parameters.*.parameter_name' => 'required_with:lab_tests.*.parameters|string',
        'lab_tests.*.parameters.*.value' => 'required_with:lab_tests.*.parameters|numeric',
        'lab_tests.*.parameters.*.unit' => 'required_with:lab_tests.*.parameters|string',
        'lab_tests.*.parameters.*.reference_range' => 'required_with:lab_tests.*.parameters|string',
        'lab_tests.*.parameters.*.flag' => 'nullable|in:High,Low,Normal',

        // Treatments
        'treatments' => 'nullable|array',
        'treatments.*.treatment_type' => 'required_with:treatments|string',
        'treatments.*.name' => 'required_with:treatments|string',
        'treatments.*.description' => 'nullable|string',
        'treatments.*.start_date' => 'required_with:treatments|date',
        'treatments.*.end_date' => 'nullable|date|after:treatments.*.start_date',
        'treatments.*.status' => 'nullable|in:planned,in_progress,completed,discontinued',
        'treatments.*.notes' => 'nullable|string',

        // Prescriptions
        'prescriptions' => 'nullable|array',
        'prescriptions.*.medication_name' => 'required_with:prescriptions|string',
        'prescriptions.*.dosage' => 'required_with:prescriptions|string',
        'prescriptions.*.frequency' => 'required_with:prescriptions|string',
        'prescriptions.*.duration' => 'required_with:prescriptions|string',
        'prescriptions.*.instructions' => 'nullable|string',
        'prescriptions.*.prescribed_date' => 'required_with:prescriptions|date',
        'prescriptions.*.refill_date' => 'nullable|date',
        'prescriptions.*.refills_allowed' => 'nullable|integer|min:0',
        'prescriptions.*.status' => 'nullable|in:active,completed,cancelled,expired',

        // Diagnoses
        'diagnoses' => 'nullable|array',
        'diagnoses.*.icd_code' => 'nullable|string',
        'diagnoses.*.diagnosis_name' => 'required_with:diagnoses|string',
        'diagnoses.*.description' => 'nullable|string',
        'diagnoses.*.certainty' => 'required_with:diagnoses|in:confirmed,probable,possible,ruled_out',
        'diagnoses.*.diagnosis_date' => 'required_with:diagnoses|date',
        'diagnoses.*.notes' => 'nullable|string',

        // Vital Signs
        'vital_signs' => 'nullable|array',
        'vital_signs.temperature' => 'nullable|numeric|between:35,42',
        'vital_signs.heart_rate' => 'nullable|integer|between:30,200',
        'vital_signs.respiratory_rate' => 'nullable|integer|between:8,40',
        'vital_signs.blood_pressure_systolic' => 'nullable|integer|between:70,250',
        'vital_signs.blood_pressure_diastolic' => 'nullable|integer|between:40,150',
        'vital_signs.oxygen_saturation' => 'nullable|numeric|between:50,100',
        'vital_signs.height' => 'nullable|numeric|between:50,250',
        'vital_signs.weight' => 'nullable|numeric|between:2,300',
        'vital_signs.blood_glucose' => 'nullable|numeric|between:20,600',
        'vital_signs.measured_at' => 'nullable|date',

        // Allergies (new allergies to add)
        'new_allergies' => 'nullable|array',
        'new_allergies.*.allergen' => 'required_with:new_allergies|string',
        'new_allergies.*.reaction' => 'required_with:new_allergies|string',
        'new_allergies.*.severity' => 'required_with:new_allergies|in:mild,moderate,severe,life_threatening',
        'new_allergies.*.diagnosed_date' => 'nullable|date',
        'new_allergies.*.notes' => 'nullable|string',

        // Immunizations (new immunizations to add)
        'new_immunizations' => 'nullable|array',
        'new_immunizations.*.vaccine_name' => 'required_with:new_immunizations|string',
        'new_immunizations.*.administration_date' => 'required_with:new_immunizations|date',
        'new_immunizations.*.next_dose_date' => 'nullable|date',
        'new_immunizations.*.lot_number' => 'nullable|string',
        'new_immunizations.*.manufacturer' => 'nullable|string',
        'new_immunizations.*.administration_site' => 'nullable|string',
        'new_immunizations.*.notes' => 'nullable|string',

        // Surgeries (new surgeries to add)
        'new_surgeries' => 'nullable|array',
        'new_surgeries.*.surgery_name' => 'required_with:new_surgeries|string',
        'new_surgeries.*.surgery_date' => 'required_with:new_surgeries|date',
        'new_surgeries.*.hospital' => 'nullable|string',
        'new_surgeries.*.surgeon_name' => 'nullable|string',
        'new_surgeries.*.reason' => 'nullable|string',
        'new_surgeries.*.complications' => 'nullable|string',
        'new_surgeries.*.notes' => 'nullable|string',
    ];

    // Only require doctor_id in validation if we couldn't get it from the logged-in user
    if (!$doctorId) {
        $rules['doctor_id'] = 'required|exists:doctors,id';
    }

    $validated = $request->validate($rules);

    try {
        DB::beginTransaction();

        // Use doctor_id from logged-in user or from request
        if (!$doctorId) {
            $doctorId = $validated['doctor_id'];
        }

        // Verify the doctor exists in database
        $doctorExists = Doctor::where('id', $doctorId)->exists();
        if (!$doctorExists) {
            throw new \Exception("Doctor with ID {$doctorId} does not exist in the database.");
        }

        // Create medical history
        $medicalHistory = MedicalHistory::create([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $doctorId,
            'visit_date' => $validated['visit_date'],
            'chief_complaint' => $validated['chief_complaint'],
            'present_illness_history' => $validated['present_illness_history'],
            'past_medical_history' => $validated['past_medical_history'] ?? null,
            'family_history' => $validated['family_history'] ?? null,
            'social_history' => $validated['social_history'] ?? null,
            'allergies' => $validated['allergies_text'] ?? null,
            'current_medications' => $validated['current_medications'] ?? null,
            'physical_examination' => $validated['physical_examination'] ?? null,
            'diagnosis' => $validated['diagnosis_text'] ?? null,
            'treatment_plan' => $validated['treatment_plan'] ?? null,
            'doctor_notes' => $validated['doctor_notes'] ?? null,
            'visit_type' => $validated['visit_type'],
            'status' => $validated['status'] ?? 'active',
        ]);

        if (!empty($validated['lab_tests'])) {
            foreach ($validated['lab_tests'] as $labTestData) {
                $labTest = LabTest::create([
                    'patient_id' => $validated['patient_id'],
                    'doctor_id' => $doctorId,
                    'medical_history_id' => $medicalHistory->id,
                    'test_name' => $labTestData['test_name'],
                    'test_category' => $labTestData['test_category'],
                    'test_date' => $labTestData['test_date'],
                    'results' => $labTestData['results'] ?? null,
                    'reference_range' => $labTestData['reference_range'] ?? null,
                    'interpretation' => $labTestData['interpretation'] ?? null,
                    'status' => $labTestData['status'] ?? 'pending',
                ]);

                // Create blood test parameters
                if (!empty($labTestData['parameters'])) {
                    foreach ($labTestData['parameters'] as $parameter) {
                        BloodTestParameter::create([
                            'lab_test_id' => $labTest->id,
                            'parameter_name' => $parameter['parameter_name'],
                            'value' => $parameter['value'],
                            'unit' => $parameter['unit'],
                            'reference_range' => $parameter['reference_range'],
                        ]);
                    }
                }
            }
        }

        // Create Treatments
        if (!empty($validated['treatments'])) {
            foreach ($validated['treatments'] as $treatmentData) {
                Treatment::create([
                    'patient_id' => $validated['patient_id'],
                    'doctor_id' => $doctorId,
                    'medical_history_id' => $medicalHistory->id,
                    'treatment_type' => $treatmentData['treatment_type'],
                    'name' => $treatmentData['name'],
                    'description' => $treatmentData['description'] ?? null,
                    'start_date' => $treatmentData['start_date'],
                    'end_date' => $treatmentData['end_date'] ?? null,
                    'status' => $treatmentData['status'] ?? 'planned',
                    'notes' => $treatmentData['notes'] ?? null,
                ]);
            }
        }

        // Create Prescriptions
        if (!empty($validated['prescriptions'])) {
            foreach ($validated['prescriptions'] as $prescriptionData) {
                Prescription::create([
                    'patient_id' => $validated['patient_id'],
                    'doctor_id' => $doctorId,
                    'medical_history_id' => $medicalHistory->id,
                    'medication_name' => $prescriptionData['medication_name'],
                    'dosage' => $prescriptionData['dosage'],
                    'frequency' => $prescriptionData['frequency'],
                    'duration' => $prescriptionData['duration'],
                    'instructions' => $prescriptionData['instructions'] ?? null,
                    'prescribed_date' => $prescriptionData['prescribed_date'],
                    'refill_date' => $prescriptionData['refill_date'] ?? null,
                    'refills_allowed' => $prescriptionData['refills_allowed'] ?? 0,
                    'status' => $prescriptionData['status'] ?? 'active',
                ]);
            }
        }

        // Create Diagnoses
        if (!empty($validated['diagnoses'])) {
            foreach ($validated['diagnoses'] as $diagnosisData) {
                Diagnosis::create([
                    'patient_id' => $validated['patient_id'],
                    'doctor_id' => $doctorId,
                    'medical_history_id' => $medicalHistory->id,
                    'icd_code' => $diagnosisData['icd_code'] ?? null,
                    'diagnosis_name' => $diagnosisData['diagnosis_name'],
                    'description' => $diagnosisData['description'] ?? null,
                    'certainty' => $diagnosisData['certainty'],
                    'diagnosis_date' => $diagnosisData['diagnosis_date'],
                    'notes' => $diagnosisData['notes'] ?? null,
                ]);
            }
        }

        // Create Vital Signs
        if (!empty($validated['vital_signs'])) {
            $vitalData = $validated['vital_signs'];
            $bmi = null;
            if (!empty($vitalData['height']) && !empty($vitalData['weight'])) {
                $heightInMeters = $vitalData['height'] / 100;
                $bmi = round($vitalData['weight'] / ($heightInMeters * $heightInMeters), 2);
            }

            VitalSign::create([
                'patient_id' => $validated['patient_id'],
                'medical_history_id' => $medicalHistory->id,
                'temperature' => $vitalData['temperature'] ?? null,
                'heart_rate' => $vitalData['heart_rate'] ?? null,
                'respiratory_rate' => $vitalData['respiratory_rate'] ?? null,
                'blood_pressure_systolic' => $vitalData['blood_pressure_systolic'] ?? null,
                'blood_pressure_diastolic' => $vitalData['blood_pressure_diastolic'] ?? null,
                'oxygen_saturation' => $vitalData['oxygen_saturation'] ?? null,
                'height' => $vitalData['height'] ?? null,
                'weight' => $vitalData['weight'] ?? null,
                'bmi' => $bmi,
                'blood_glucose' => $vitalData['blood_glucose'] ?? null,
                'measured_at' => $vitalData['measured_at'] ?? now(),
            ]);
        }

        // Create New Allergies
        if (!empty($validated['new_allergies'])) {
            foreach ($validated['new_allergies'] as $allergyData) {
                Allergy::create([
                    'patient_id' => $validated['patient_id'],
                    'allergen' => $allergyData['allergen'],
                    'reaction' => $allergyData['reaction'],
                    'severity' => $allergyData['severity'],
                    'diagnosed_date' => $allergyData['diagnosed_date'] ?? now(),
                    'notes' => $allergyData['notes'] ?? null,
                ]);
            }
        }

        // Create New Immunizations
        if (!empty($validated['new_immunizations'])) {
            foreach ($validated['new_immunizations'] as $immunizationData) {
                Immunization::create([
                    'patient_id' => $validated['patient_id'],
                    'doctor_id' => $doctorId,
                    'vaccine_name' => $immunizationData['vaccine_name'],
                    'administration_date' => $immunizationData['administration_date'],
                    'next_dose_date' => $immunizationData['next_dose_date'] ?? null,
                    'lot_number' => $immunizationData['lot_number'] ?? null,
                    'manufacturer' => $immunizationData['manufacturer'] ?? null,
                    'administered_by' => $immunizationData['administered_by'] ?? (Auth::check() ? Auth::user()->fname . ' ' . Auth::user()->lname : null),
                    'administration_site' => $immunizationData['administration_site'] ?? null,
                    'notes' => $immunizationData['notes'] ?? null,
                ]);
            }
        }

        // Create New Surgeries
        if (!empty($validated['new_surgeries'])) {
            foreach ($validated['new_surgeries'] as $surgeryData) {
                Surgery::create([
                    'patient_id' => $validated['patient_id'],
                    'surgery_name' => $surgeryData['surgery_name'],
                    'surgery_date' => $surgeryData['surgery_date'],
                    'hospital' => $surgeryData['hospital'] ?? null,
                    'surgeon_name' => $surgeryData['surgeon_name'] ?? null,
                    'reason' => $surgeryData['reason'] ?? null,
                    'complications' => $surgeryData['complications'] ?? null,
                    'notes' => $surgeryData['notes'] ?? null,
                ]);
            }
        }

        DB::commit();

        $medicalHistory->load([
            'patient.user',
            'doctor.user',
            'labTests.bloodTestParameters',
            'treatments',
            'prescriptions',
            'diagnoses',
            'vitalSigns'
        ]);

        $allergies = Allergy::where('patient_id', $validated['patient_id'])->get();
        $immunizations = Immunization::where('patient_id', $validated['patient_id'])->with('doctor.user')->get();
        $surgeries = Surgery::where('patient_id', $validated['patient_id'])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'medical_history' => $medicalHistory,
                'allergies' => $allergies,
                'immunizations' => $immunizations,
                'surgeries' => $surgeries,
            ],
            'message' => 'Medical history and all related data created successfully'
        ], 201);

    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();

        // Log the error for debugging
        \Log::error('Medical history creation failed:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create medical history',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function update(Request $request, $id)
{
    try {
        DB::beginTransaction();

        // Find the medical history
        $medicalHistory = MedicalHistory::findOrFail($id);

        // Authorization check: Only the doctor who created it or an admin can update
        $user = Auth::user();
        $isCreator = $user->doctor && $medicalHistory->doctor_id === $user->doctor->id;
        $isAdmin = $user->is_admin;
        if (!$isCreator && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this medical history',
                'error' => 'Unauthorized access. Only the doctor who created this record or an admin can update it.'
            ], 403);
        }

        // Get doctor_id from logged-in user (same doctor or admin)
        $doctorId = null;
        if ($user->doctor) {
            $doctorId = $user->doctor->id;
        }

        // Build validation rules dynamically
        $rules = [
            // Medical History fields
            'patient_id' => 'sometimes|exists:patients,id',
            'visit_date' => 'sometimes|date',
            'chief_complaint' => 'sometimes|string',
            'present_illness_history' => 'sometimes|string',
            'past_medical_history' => 'nullable|string',
            'family_history' => 'nullable|string',
            'social_history' => 'nullable|string',
            'allergies_text' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'physical_examination' => 'nullable|string',
            'diagnosis_text' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
            'visit_type' => 'sometimes|in:initial,follow_up,emergency,consultation',
            'status' => 'sometimes|in:active,resolved,inactive',

            // Lab Tests (for updating/adding)
            'lab_tests' => 'nullable|array',
            'lab_tests.*.id' => 'nullable|exists:lab_tests,id',
            'lab_tests.*.test_name' => 'required_with:lab_tests|string',
            'lab_tests.*.test_category' => 'required_with:lab_tests|string',
            'lab_tests.*.test_date' => 'required_with:lab_tests|date',
            'lab_tests.*.results' => 'nullable|string',
            'lab_tests.*.reference_range' => 'nullable|string',
            'lab_tests.*.interpretation' => 'nullable|string',
            'lab_tests.*.status' => 'nullable|in:pending,in_progress,completed,cancelled',

            // Blood Test Parameters
            'lab_tests.*.parameters' => 'nullable|array',
            'lab_tests.*.parameters.*.id' => 'nullable|exists:blood_test_parameters,id',
            'lab_tests.*.parameters.*.parameter_name' => 'required_with:lab_tests.*.parameters|string',
            'lab_tests.*.parameters.*.value' => 'required_with:lab_tests.*.parameters|numeric',
            'lab_tests.*.parameters.*.unit' => 'required_with:lab_tests.*.parameters|string',
            'lab_tests.*.parameters.*.reference_range' => 'required_with:lab_tests.*.parameters|string',
            'lab_tests.*.parameters.*.flag' => 'nullable|in:High,Low,Normal',

            // Treatments (for updating/adding)
            'treatments' => 'nullable|array',
            'treatments.*.id' => 'nullable|exists:treatments,id',
            'treatments.*.treatment_type' => 'required_with:treatments|string',
            'treatments.*.name' => 'required_with:treatments|string',
            'treatments.*.description' => 'nullable|string',
            'treatments.*.start_date' => 'required_with:treatments|date',
            'treatments.*.end_date' => 'nullable|date|after:treatments.*.start_date',
            'treatments.*.status' => 'nullable|in:planned,in_progress,completed,discontinued',
            'treatments.*.notes' => 'nullable|string',

            // Prescriptions (for updating/adding)
            'prescriptions' => 'nullable|array',
            'prescriptions.*.id' => 'nullable|exists:prescriptions,id',
            'prescriptions.*.medication_name' => 'required_with:prescriptions|string',
            'prescriptions.*.dosage' => 'required_with:prescriptions|string',
            'prescriptions.*.frequency' => 'required_with:prescriptions|string',
            'prescriptions.*.duration' => 'required_with:prescriptions|string',
            'prescriptions.*.instructions' => 'nullable|string',
            'prescriptions.*.prescribed_date' => 'required_with:prescriptions|date',
            'prescriptions.*.refill_date' => 'nullable|date',
            'prescriptions.*.refills_allowed' => 'nullable|integer|min:0',
            'prescriptions.*.status' => 'nullable|in:active,completed,cancelled,expired',

            // Diagnoses (for updating/adding)
            'diagnoses' => 'nullable|array',
            'diagnoses.*.id' => 'nullable|exists:diagnoses,id',
            'diagnoses.*.icd_code' => 'nullable|string',
            'diagnoses.*.diagnosis_name' => 'required_with:diagnoses|string',
            'diagnoses.*.description' => 'nullable|string',
            'diagnoses.*.certainty' => 'required_with:diagnoses|in:confirmed,probable,possible,ruled_out',
            'diagnoses.*.diagnosis_date' => 'required_with:diagnoses|date',
            'diagnoses.*.notes' => 'nullable|string',

            // Vital Signs (for updating)
            'vital_signs' => 'nullable|array',
            'vital_signs.id' => 'nullable|exists:vital_signs,id',
            'vital_signs.temperature' => 'nullable|numeric|between:35,42',
            'vital_signs.heart_rate' => 'nullable|integer|between:30,200',
            'vital_signs.respiratory_rate' => 'nullable|integer|between:8,40',
            'vital_signs.blood_pressure_systolic' => 'nullable|integer|between:70,250',
            'vital_signs.blood_pressure_diastolic' => 'nullable|integer|between:40,150',
            'vital_signs.oxygen_saturation' => 'nullable|numeric|between:50,100',
            'vital_signs.height' => 'nullable|numeric|between:50,250',
            'vital_signs.weight' => 'nullable|numeric|between:2,300',
            'vital_signs.blood_glucose' => 'nullable|numeric|between:20,600',
            'vital_signs.measured_at' => 'nullable|date',

            // Allergies (new allergies to add)
            'new_allergies' => 'nullable|array',
            'new_allergies.*.allergen' => 'required_with:new_allergies|string',
            'new_allergies.*.reaction' => 'required_with:new_allergies|string',
            'new_allergies.*.severity' => 'required_with:new_allergies|in:mild,moderate,severe,life_threatening',
            'new_allergies.*.diagnosed_date' => 'nullable|date',
            'new_allergies.*.notes' => 'nullable|string',

            // Existing allergies to delete
            'delete_allergy_ids' => 'nullable|array',
            'delete_allergy_ids.*' => 'exists:allergies,id',

            // Immunizations (new immunizations to add)
            'new_immunizations' => 'nullable|array',
            'new_immunizations.*.vaccine_name' => 'required_with:new_immunizations|string',
            'new_immunizations.*.administration_date' => 'required_with:new_immunizations|date',
            'new_immunizations.*.next_dose_date' => 'nullable|date',
            'new_immunizations.*.lot_number' => 'nullable|string',
            'new_immunizations.*.manufacturer' => 'nullable|string',
            'new_immunizations.*.administration_site' => 'nullable|string',
            'new_immunizations.*.notes' => 'nullable|string',

            // Existing immunizations to delete
            'delete_immunization_ids' => 'nullable|array',
            'delete_immunization_ids.*' => 'exists:immunizations,id',

            // Surgeries (new surgeries to add)
            'new_surgeries' => 'nullable|array',
            'new_surgeries.*.surgery_name' => 'required_with:new_surgeries|string',
            'new_surgeries.*.surgery_date' => 'required_with:new_surgeries|date',
            'new_surgeries.*.hospital' => 'nullable|string',
            'new_surgeries.*.surgeon_name' => 'nullable|string',
            'new_surgeries.*.reason' => 'nullable|string',
            'new_surgeries.*.complications' => 'nullable|string',
            'new_surgeries.*.notes' => 'nullable|string',

            // Existing surgeries to delete
            'delete_surgery_ids' => 'nullable|array',
            'delete_surgery_ids.*' => 'exists:surgeries,id',
        ];

        // Only require doctor_id in validation if we couldn't get it from the logged-in user
        if (!$doctorId) {
            $rules['doctor_id'] = 'sometimes|exists:doctors,id';
        }

        $validated = $request->validate($rules);

        // Update medical history fields
        $updateData = [];
        $allowedFields = [
            'patient_id', 'visit_date', 'chief_complaint', 'present_illness_history',
            'past_medical_history', 'family_history', 'social_history', 'allergies_text',
            'current_medications', 'physical_examination', 'diagnosis_text',
            'treatment_plan', 'doctor_notes', 'visit_type', 'status'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $validated)) {
                // Map allergies_text to allergies column
                if ($field === 'allergies_text') {
                    $updateData['allergies'] = $validated[$field];
                } else {
                    $updateData[$field] = $validated[$field];
                }
            }
        }

        // Add doctor_id if provided and user is admin (only admin can change doctor)
        if ($isAdmin && isset($validated['doctor_id'])) {
            $updateData['doctor_id'] = $validated['doctor_id'];
        }

        if (!empty($updateData)) {
            $medicalHistory->update($updateData);
        }

        // Update or Create Lab Tests
        if (!empty($validated['lab_tests'])) {
            foreach ($validated['lab_tests'] as $labTestData) {
                if (isset($labTestData['id'])) {
                    // Update existing lab test
                    $labTest = LabTest::where('id', $labTestData['id'])
                        ->where('medical_history_id', $medicalHistory->id)
                        ->first();

                    if ($labTest) {
                        $labTest->update([
                            'test_name' => $labTestData['test_name'],
                            'test_category' => $labTestData['test_category'],
                            'test_date' => $labTestData['test_date'],
                            'results' => $labTestData['results'] ?? null,
                            'reference_range' => $labTestData['reference_range'] ?? null,
                            'interpretation' => $labTestData['interpretation'] ?? null,
                            'status' => $labTestData['status'] ?? 'pending',
                        ]);

                        // Update parameters
                        if (!empty($labTestData['parameters'])) {
                            foreach ($labTestData['parameters'] as $parameter) {
                                if (isset($parameter['id'])) {
                                    $bloodParam = BloodTestParameter::where('id', $parameter['id'])
                                        ->where('lab_test_id', $labTest->id)
                                        ->first();
                                    if ($bloodParam) {
                                        $bloodParam->update([
                                            'parameter_name' => $parameter['parameter_name'],
                                            'value' => $parameter['value'],
                                            'unit' => $parameter['unit'],
                                            'reference_range' => $parameter['reference_range'],
                                        ]);
                                    }
                                } else {
                                    BloodTestParameter::create([
                                        'lab_test_id' => $labTest->id,
                                        'parameter_name' => $parameter['parameter_name'],
                                        'value' => $parameter['value'],
                                        'unit' => $parameter['unit'],
                                        'reference_range' => $parameter['reference_range'],
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    // Create new lab test
                    $labTest = LabTest::create([
                        'patient_id' => $medicalHistory->patient_id,
                        'doctor_id' => $doctorId ?? $medicalHistory->doctor_id,
                        'medical_history_id' => $medicalHistory->id,
                        'test_name' => $labTestData['test_name'],
                        'test_category' => $labTestData['test_category'],
                        'test_date' => $labTestData['test_date'],
                        'results' => $labTestData['results'] ?? null,
                        'reference_range' => $labTestData['reference_range'] ?? null,
                        'interpretation' => $labTestData['interpretation'] ?? null,
                        'status' => $labTestData['status'] ?? 'pending',
                    ]);

                    // Create parameters for new lab test
                    if (!empty($labTestData['parameters'])) {
                        foreach ($labTestData['parameters'] as $parameter) {
                            BloodTestParameter::create([
                                'lab_test_id' => $labTest->id,
                                'parameter_name' => $parameter['parameter_name'],
                                'value' => $parameter['value'],
                                'unit' => $parameter['unit'],
                                'reference_range' => $parameter['reference_range'],
                            ]);
                        }
                    }
                }
            }
        }

        // Update or Create Treatments
        if (!empty($validated['treatments'])) {
            foreach ($validated['treatments'] as $treatmentData) {
                if (isset($treatmentData['id'])) {
                    $treatment = Treatment::where('id', $treatmentData['id'])
                        ->where('medical_history_id', $medicalHistory->id)
                        ->first();
                    if ($treatment) {
                        $treatment->update([
                            'treatment_type' => $treatmentData['treatment_type'],
                            'name' => $treatmentData['name'],
                            'description' => $treatmentData['description'] ?? null,
                            'start_date' => $treatmentData['start_date'],
                            'end_date' => $treatmentData['end_date'] ?? null,
                            'status' => $treatmentData['status'] ?? 'planned',
                            'notes' => $treatmentData['notes'] ?? null,
                        ]);
                    }
                } else {
                    Treatment::create([
                        'patient_id' => $medicalHistory->patient_id,
                        'doctor_id' => $doctorId ?? $medicalHistory->doctor_id,
                        'medical_history_id' => $medicalHistory->id,
                        'treatment_type' => $treatmentData['treatment_type'],
                        'name' => $treatmentData['name'],
                        'description' => $treatmentData['description'] ?? null,
                        'start_date' => $treatmentData['start_date'],
                        'end_date' => $treatmentData['end_date'] ?? null,
                        'status' => $treatmentData['status'] ?? 'planned',
                        'notes' => $treatmentData['notes'] ?? null,
                    ]);
                }
            }
        }

        // Update or Create Prescriptions
        if (!empty($validated['prescriptions'])) {
            foreach ($validated['prescriptions'] as $prescriptionData) {
                if (isset($prescriptionData['id'])) {
                    $prescription = Prescription::where('id', $prescriptionData['id'])
                        ->where('medical_history_id', $medicalHistory->id)
                        ->first();
                    if ($prescription) {
                        $prescription->update([
                            'medication_name' => $prescriptionData['medication_name'],
                            'dosage' => $prescriptionData['dosage'],
                            'frequency' => $prescriptionData['frequency'],
                            'duration' => $prescriptionData['duration'],
                            'instructions' => $prescriptionData['instructions'] ?? null,
                            'prescribed_date' => $prescriptionData['prescribed_date'],
                            'refill_date' => $prescriptionData['refill_date'] ?? null,
                            'refills_allowed' => $prescriptionData['refills_allowed'] ?? 0,
                            'status' => $prescriptionData['status'] ?? 'active',
                        ]);
                    }
                } else {
                    Prescription::create([
                        'patient_id' => $medicalHistory->patient_id,
                        'doctor_id' => $doctorId ?? $medicalHistory->doctor_id,
                        'medical_history_id' => $medicalHistory->id,
                        'medication_name' => $prescriptionData['medication_name'],
                        'dosage' => $prescriptionData['dosage'],
                        'frequency' => $prescriptionData['frequency'],
                        'duration' => $prescriptionData['duration'],
                        'instructions' => $prescriptionData['instructions'] ?? null,
                        'prescribed_date' => $prescriptionData['prescribed_date'],
                        'refill_date' => $prescriptionData['refill_date'] ?? null,
                        'refills_allowed' => $prescriptionData['refills_allowed'] ?? 0,
                        'status' => $prescriptionData['status'] ?? 'active',
                    ]);
                }
            }
        }

        // Update or Create Diagnoses
        if (!empty($validated['diagnoses'])) {
            foreach ($validated['diagnoses'] as $diagnosisData) {
                if (isset($diagnosisData['id'])) {
                    $diagnosis = Diagnosis::where('id', $diagnosisData['id'])
                        ->where('medical_history_id', $medicalHistory->id)
                        ->first();
                    if ($diagnosis) {
                        $diagnosis->update([
                            'icd_code' => $diagnosisData['icd_code'] ?? null,
                            'diagnosis_name' => $diagnosisData['diagnosis_name'],
                            'description' => $diagnosisData['description'] ?? null,
                            'certainty' => $diagnosisData['certainty'],
                            'diagnosis_date' => $diagnosisData['diagnosis_date'],
                            'notes' => $diagnosisData['notes'] ?? null,
                        ]);
                    }
                } else {
                    Diagnosis::create([
                        'patient_id' => $medicalHistory->patient_id,
                        'doctor_id' => $doctorId ?? $medicalHistory->doctor_id,
                        'medical_history_id' => $medicalHistory->id,
                        'icd_code' => $diagnosisData['icd_code'] ?? null,
                        'diagnosis_name' => $diagnosisData['diagnosis_name'],
                        'description' => $diagnosisData['description'] ?? null,
                        'certainty' => $diagnosisData['certainty'],
                        'diagnosis_date' => $diagnosisData['diagnosis_date'],
                        'notes' => $diagnosisData['notes'] ?? null,
                    ]);
                }
            }
        }

        // Update Vital Signs
        if (!empty($validated['vital_signs'])) {
            $vitalData = $validated['vital_signs'];
            $bmi = null;
            if (!empty($vitalData['height']) && !empty($vitalData['weight'])) {
                $heightInMeters = $vitalData['height'] / 100;
                $bmi = round($vitalData['weight'] / ($heightInMeters * $heightInMeters), 2);
            }

            if (isset($vitalData['id'])) {
                $vitalSign = VitalSign::where('id', $vitalData['id'])
                    ->where('medical_history_id', $medicalHistory->id)
                    ->first();
                if ($vitalSign) {
                    $vitalSign->update([
                        'temperature' => $vitalData['temperature'] ?? null,
                        'heart_rate' => $vitalData['heart_rate'] ?? null,
                        'respiratory_rate' => $vitalData['respiratory_rate'] ?? null,
                        'blood_pressure_systolic' => $vitalData['blood_pressure_systolic'] ?? null,
                        'blood_pressure_diastolic' => $vitalData['blood_pressure_diastolic'] ?? null,
                        'oxygen_saturation' => $vitalData['oxygen_saturation'] ?? null,
                        'height' => $vitalData['height'] ?? null,
                        'weight' => $vitalData['weight'] ?? null,
                        'bmi' => $bmi,
                        'blood_glucose' => $vitalData['blood_glucose'] ?? null,
                        'measured_at' => $vitalData['measured_at'] ?? now(),
                    ]);
                }
            } else {
                VitalSign::create([
                    'patient_id' => $medicalHistory->patient_id,
                    'medical_history_id' => $medicalHistory->id,
                    'temperature' => $vitalData['temperature'] ?? null,
                    'heart_rate' => $vitalData['heart_rate'] ?? null,
                    'respiratory_rate' => $vitalData['respiratory_rate'] ?? null,
                    'blood_pressure_systolic' => $vitalData['blood_pressure_systolic'] ?? null,
                    'blood_pressure_diastolic' => $vitalData['blood_pressure_diastolic'] ?? null,
                    'oxygen_saturation' => $vitalData['oxygen_saturation'] ?? null,
                    'height' => $vitalData['height'] ?? null,
                    'weight' => $vitalData['weight'] ?? null,
                    'bmi' => $bmi,
                    'blood_glucose' => $vitalData['blood_glucose'] ?? null,
                    'measured_at' => $vitalData['measured_at'] ?? now(),
                ]);
            }
        }

        // Create New Allergies
        if (!empty($validated['new_allergies'])) {
            foreach ($validated['new_allergies'] as $allergyData) {
                Allergy::create([
                    'patient_id' => $medicalHistory->patient_id,
                    'allergen' => $allergyData['allergen'],
                    'reaction' => $allergyData['reaction'],
                    'severity' => $allergyData['severity'],
                    'diagnosed_date' => $allergyData['diagnosed_date'] ?? now(),
                    'notes' => $allergyData['notes'] ?? null,
                ]);
            }
        }

        // Delete specified allergies
        if (!empty($validated['delete_allergy_ids'])) {
            Allergy::whereIn('id', $validated['delete_allergy_ids'])
                ->where('patient_id', $medicalHistory->patient_id)
                ->delete();
        }

        // Create New Immunizations
        if (!empty($validated['new_immunizations'])) {
            foreach ($validated['new_immunizations'] as $immunizationData) {
                Immunization::create([
                    'patient_id' => $medicalHistory->patient_id,
                    'doctor_id' => $doctorId ?? $medicalHistory->doctor_id,
                    'vaccine_name' => $immunizationData['vaccine_name'],
                    'administration_date' => $immunizationData['administration_date'],
                    'next_dose_date' => $immunizationData['next_dose_date'] ?? null,
                    'lot_number' => $immunizationData['lot_number'] ?? null,
                    'manufacturer' => $immunizationData['manufacturer'] ?? null,
                    'administered_by' => $immunizationData['administered_by'] ?? (Auth::user()->fname . ' ' . Auth::user()->lname),
                    'administration_site' => $immunizationData['administration_site'] ?? null,
                    'notes' => $immunizationData['notes'] ?? null,
                ]);
            }
        }

        // Delete specified immunizations
        if (!empty($validated['delete_immunization_ids'])) {
            Immunization::whereIn('id', $validated['delete_immunization_ids'])
                ->where('patient_id', $medicalHistory->patient_id)
                ->delete();
        }

        // Create New Surgeries
        if (!empty($validated['new_surgeries'])) {
            foreach ($validated['new_surgeries'] as $surgeryData) {
                Surgery::create([
                    'patient_id' => $medicalHistory->patient_id,
                    'surgery_name' => $surgeryData['surgery_name'],
                    'surgery_date' => $surgeryData['surgery_date'],
                    'hospital' => $surgeryData['hospital'] ?? null,
                    'surgeon_name' => $surgeryData['surgeon_name'] ?? null,
                    'reason' => $surgeryData['reason'] ?? null,
                    'complications' => $surgeryData['complications'] ?? null,
                    'notes' => $surgeryData['notes'] ?? null,
                ]);
            }
        }

        // Delete specified surgeries
        if (!empty($validated['delete_surgery_ids'])) {
            Surgery::whereIn('id', $validated['delete_surgery_ids'])
                ->where('patient_id', $medicalHistory->patient_id)
                ->delete();
        }

        DB::commit();

        // Load all relationships
        $medicalHistory->load([
            'patient.user',
            'doctor.user',
            'labTests.bloodTestParameters',
            'treatments',
            'prescriptions',
            'diagnoses',
            'vitalSigns'
        ]);

        $allergies = Allergy::where('patient_id', $medicalHistory->patient_id)->get();
        $immunizations = Immunization::where('patient_id', $medicalHistory->patient_id)->with('doctor.user')->get();
        $surgeries = Surgery::where('patient_id', $medicalHistory->patient_id)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'medical_history' => $medicalHistory,
                'allergies' => $allergies,
                'immunizations' => $immunizations,
                'surgeries' => $surgeries,
            ],
            'message' => 'Medical history updated successfully'
        ], 200);

    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Medical history update failed:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update medical history',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
