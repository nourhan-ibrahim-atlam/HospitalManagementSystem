<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\UpdateRequest;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateRequestController extends Controller
{
    /**
     * Create update request for patient (by patient or doctor)
     * IMPORTANT: This only CREATES a request, does NOT update actual data
     */
    public function requestPatientUpdate(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'field_name' => 'required|string|in:' . implode(',', UpdateRequest::getAllowedFieldsForTarget('patient')),
            'new_value' => 'required|string|max:255',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $patient = Patient::with('user')->find($request->patient_id);


        if ($user->isPatient()) {

            if (!$user->patient || $user->patient->id != $patient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only request updates for your own profile'
                ], 403);
            }
        } elseif ($user->isDoctor()) {
            if (!$user->doctor->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending admin approval'
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check for existing pending request for the same field
        $existingRequest = UpdateRequest::where('target_type', UpdateRequest::TARGET_PATIENT)
            ->where('target_id', $patient->id)
            ->where('field_name', $request->field_name)
            ->where('status', UpdateRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A pending request already exists for this field. Please wait for admin review.',
                'existing_request_id' => $existingRequest->id
            ], 422);
        }

        // Get current value (what will be replaced if approved)
        $currentValue = $this->getCurrentPatientFieldValue($patient, $request->field_name);

        DB::beginTransaction();

        try {
            // ONLY CREATE REQUEST - NO ACTUAL UPDATE
            $updateRequest = UpdateRequest::create([
                'patient_id' => $patient->id,
                'user_id' => $user->id,
                'requester_type' => $user->isPatient() ? UpdateRequest::REQUESTER_PATIENT : UpdateRequest::REQUESTER_DOCTOR,
                'target_type' => UpdateRequest::TARGET_PATIENT,
                'target_id' => $patient->id,
                'field_name' => $request->field_name,
                'old_value' => $currentValue,
                'new_value' => $request->new_value,
                'reason' => $request->reason,
                'status' => UpdateRequest::STATUS_PENDING  // PENDING - NOT APPLIED YET
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Update request submitted successfully. It will be reviewed by an administrator.',
                'data' => $this->formatUpdateRequest($updateRequest)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create update request for doctor (by doctor only)
     * IMPORTANT: This only CREATES a request, does NOT update actual data
     */
    public function requestDoctorUpdate(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only doctors can request updates for their own profile
        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'message' => 'Only doctors can request updates for their profile'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'field_name' => 'required|string|in:' . implode(',', UpdateRequest::getAllowedFieldsForTarget('doctor')),
            'new_value' => 'required|string|max:255',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = $user->doctor;

        // Check for existing pending request
        $existingRequest = UpdateRequest::where('target_type', UpdateRequest::TARGET_DOCTOR)
            ->where('target_id', $doctor->id)
            ->where('field_name', $request->field_name)
            ->where('status', UpdateRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A pending request already exists for this field. Please wait for admin review.',
                'existing_request_id' => $existingRequest->id
            ], 422);
        }

        // Get current value
        $currentValue = $this->getCurrentDoctorFieldValue($doctor, $request->field_name);

        DB::beginTransaction();

        try {
            // ONLY CREATE REQUEST - NO ACTUAL UPDATE
            $updateRequest = UpdateRequest::create([
                'doctor_id' => $doctor->id,
                'user_id' => $user->id,
                'requester_type' => UpdateRequest::REQUESTER_DOCTOR,
                'target_type' => UpdateRequest::TARGET_DOCTOR,
                'target_id' => $doctor->id,
                'field_name' => $request->field_name,
                'old_value' => $currentValue,
                'new_value' => $request->new_value,
                'reason' => $request->reason,
                'status' => UpdateRequest::STATUS_PENDING  // PENDING - NOT APPLIED YET
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Doctor profile update request submitted successfully. It will be reviewed by an administrator.',
                'data' => $this->formatUpdateRequest($updateRequest)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin approves and APPLIES the update
     * This is the ONLY place where actual data is updated
     */
    public function approve(Request $request, UpdateRequest $updateRequest): JsonResponse
    {
        $user = auth()->user();

        // Only admin can approve
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can approve update requests'
            ], 403);
        }

        if (!$updateRequest->canBeReviewed()) {
            return response()->json([
                'success' => false,
                'message' => 'This request cannot be approved as it is already ' . $updateRequest->status
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reviewer_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // APPLY THE UPDATE TO ACTUAL DATA
            $this->applyUpdate($updateRequest);

            // Mark request as approved
            $updateRequest->update([
                'status' => UpdateRequest::STATUS_APPROVED,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $request->reviewer_notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Update request approved and applied successfully',
                'data' => $this->formatUpdateRequest($updateRequest->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin rejects the update request (no changes applied)
     */
    public function reject(Request $request, UpdateRequest $updateRequest): JsonResponse
    {
        $user = auth()->user();

        // Only admin can reject
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can reject update requests'
            ], 403);
        }

        if (!$updateRequest->canBeReviewed()) {
            return response()->json([
                'success' => false,
                'message' => 'This request cannot be rejected as it is already ' . $updateRequest->status
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reviewer_notes' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a reason for rejection',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // NO UPDATE APPLIED - just mark as rejected
            $updateRequest->update([
                'status' => UpdateRequest::STATUS_REJECTED,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $request->reviewer_notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Update request rejected. No changes were made to the user data.',
                'data' => $this->formatUpdateRequest($updateRequest->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel update request (by requester)
     */
    public function cancel(Request $request, UpdateRequest $updateRequest): JsonResponse
    {
        $user = auth()->user();

        // Only the requester can cancel
        if ($updateRequest->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the requester can cancel this request'
            ], 403);
        }

        if (!$updateRequest->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be cancelled'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            $updateRequest->update([
                'status' => UpdateRequest::STATUS_CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->cancellation_reason
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request cancelled successfully. No changes were made.',
                'data' => $this->formatUpdateRequest($updateRequest)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all update requests (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = UpdateRequest::with(['requester', 'reviewer', 'patient.user', 'doctor.user']);

        // Filter by role
        if ($user->isPatient()) {
            // Patient can see their own requests
            $query->where(function($q) use ($user) {
                $q->where('target_type', UpdateRequest::TARGET_PATIENT)
                  ->where('target_id', $user->patient->id)
                  ->orWhere('user_id', $user->id);
            });
        } elseif ($user->isDoctor()) {
            // Doctor can see requests they made
            $doctor = $user->doctor;
            $query->where(function($q) use ($doctor, $user) {
                $q->where('target_type', UpdateRequest::TARGET_DOCTOR)
                  ->where('target_id', $doctor->id)
                  ->orWhere('user_id', $user->id);
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('target_type') && $request->target_type) {
            $query->where('target_type', $request->target_type);
        }

        if ($request->has('field_name') && $request->field_name) {
            $query->where('field_name', $request->field_name);
        }

        $requests = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn($r) => $this->formatUpdateRequest($r)),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total()
            ]
        ]);
    }

    /**
     * Get single update request
     */
    public function show(UpdateRequest $updateRequest): JsonResponse
    {
        $user = auth()->user();

        // Check authorization
        if (!$this->canViewRequest($user, $updateRequest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this request'
            ], 403);
        }

        $updateRequest->load(['requester', 'reviewer', 'patient.user', 'doctor.user']);

        return response()->json([
            'success' => true,
            'data' => $this->formatUpdateRequest($updateRequest)
        ]);
    }

    /**
     * Get pending requests for admin
     */
    public function pendingRequests(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $requests = UpdateRequest::where('status', UpdateRequest::STATUS_PENDING)
            ->with(['requester', 'patient.user', 'doctor.user'])
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn($r) => $this->formatUpdateRequest($r)),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total()
            ]
        ]);
    }

    /**
     * Get my requests (for authenticated user)
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = UpdateRequest::where('user_id', $user->id)
            ->with(['reviewer', 'patient.user', 'doctor.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn($r) => $this->formatUpdateRequest($r)),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total()
            ]
        ]);
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Get current value from actual data (not from request)
     */
    private function getCurrentPatientFieldValue(Patient $patient, string $fieldName): ?string
    {
        // Check if field is in user table
        if (in_array($fieldName, ['fname', 'lname', 'phone', 'email'])) {
            return $patient->user->$fieldName ?? null;
        }

        // Check if field is in patient table
        return $patient->$fieldName ?? null;
    }

    /**
     * Get current value from actual data (not from request)
     */
    private function getCurrentDoctorFieldValue(Doctor $doctor, string $fieldName): ?string
    {
        // Check if field is in user table
        if (in_array($fieldName, ['fname', 'lname', 'phone', 'email'])) {
            return $doctor->user->$fieldName ?? null;
        }

        // Check if field is in doctor table
        return $doctor->$fieldName ?? null;
    }

    /**
     * APPLY the update to actual data (only called when admin approves)
     */
    private function applyUpdate(UpdateRequest $updateRequest): void
    {
        if ($updateRequest->target_type === UpdateRequest::TARGET_PATIENT) {
            $patient = Patient::with('user')->find($updateRequest->target_id);

            if (!$patient) {
                throw new \Exception('Patient not found');
            }

            // Update user fields
            if (in_array($updateRequest->field_name, ['fname', 'lname', 'phone', 'email'])) {
                $patient->user->update([
                    $updateRequest->field_name => $updateRequest->new_value
                ]);
            }
            // Update patient fields
            else {
                $patient->update([
                    $updateRequest->field_name => $updateRequest->new_value
                ]);
            }
        }
        elseif ($updateRequest->target_type === UpdateRequest::TARGET_DOCTOR) {
            $doctor = Doctor::with('user')->find($updateRequest->target_id);

            if (!$doctor) {
                throw new \Exception('Doctor not found');
            }

            // Update user fields
            if (in_array($updateRequest->field_name, ['fname', 'lname', 'phone', 'email'])) {
                $doctor->user->update([
                    $updateRequest->field_name => $updateRequest->new_value
                ]);
            }
            // Update doctor fields
            else {
                $doctor->update([
                    $updateRequest->field_name => $updateRequest->new_value
                ]);
            }
        }
    }

    /**
     * Format update request for response
     */
    private function formatUpdateRequest(UpdateRequest $request): array
    {
        $data = [
            'id' => $request->id,
            'field_name' => $request->field_name,
            'field_label' => $request->field_label,
            'current_value' => $request->old_value,
            'requested_value' => $request->new_value,
            'reason' => $request->reason,
            'status' => $request->status,
            'status_badge' => $request->status_badge,
            'requester' => $request->requester ? [
                'id' => $request->requester->id,
                'name' => $request->requester->fname . ' ' . $request->requester->lname,
                'type' => $request->requester_type
            ] : null,
            'created_at' => $request->created_at->toDateTimeString(),
            'updated_at' => $request->updated_at->toDateTimeString()
        ];

        // Add reviewer info if reviewed
        if ($request->reviewed_by && $request->reviewer) {
            $data['reviewer'] = [
                'id' => $request->reviewer->id,
                'name' => $request->reviewer->fname . ' ' . $request->reviewer->lname,
                'notes' => $request->reviewer_notes,
                'reviewed_at' => $request->reviewed_at?->toDateTimeString()
            ];
        }

        // Add cancellation info if cancelled
        if ($request->cancelled_by) {
            $data['cancelled_by'] = $request->cancelledBy?->fname . ' ' . $request->cancelledBy?->lname;
            $data['cancelled_at'] = $request->cancelled_at?->toDateTimeString();
            $data['cancellation_reason'] = $request->cancellation_reason;
        }

        // Add target info
        if ($request->target_type === UpdateRequest::TARGET_PATIENT && $request->patient) {
            $data['target'] = [
                'type' => 'patient',
                'id' => $request->patient->id,
                'name' => $request->patient->user->fname . ' ' . $request->patient->user->lname
            ];
        } elseif ($request->target_type === UpdateRequest::TARGET_DOCTOR && $request->doctor) {
            $data['target'] = [
                'type' => 'doctor',
                'id' => $request->doctor->id,
                'name' => $request->doctor->user->fname . ' ' . $request->doctor->user->lname
            ];
        }

        return $data;
    }

    /**
     * Check if user can view request
     */
    private function canViewRequest(User $user, UpdateRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPatient()) {
            return $request->user_id === $user->id
                || ($request->target_type === UpdateRequest::TARGET_PATIENT && $request->target_id === $user->patient->id);
        }

        if ($user->isDoctor()) {
            return $request->user_id === $user->id
                || ($request->target_type === UpdateRequest::TARGET_DOCTOR && $request->target_id === $user->doctor->id);
        }

        return false;
    }
}
