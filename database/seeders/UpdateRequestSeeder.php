<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateRequestSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('update_requests')->count() > 0) {
            return;
        }

        $adminId    = User::where('role', 'admin')->value('id');
        $patients   = Patient::with('user')->take(50)->get();
        $doctors    = Doctor::where('is_approved', true)->take(15)->get();

        if ($patients->isEmpty() && $doctors->isEmpty()) {
            $this->command->warn('No patients or doctors found. Skipping UpdateRequestSeeder.');
            return;
        }

        $now              = Carbon::now();
        $patientFields    = ['blood_type', 'emergency_contact', 'phone', 'address', 'fname', 'lname'];
        $doctorFields     = ['specialization', 'phone'];
        $records          = [];

        $rejectionNotes = [
            'Provided documentation does not match records on file.',
            'Insufficient supporting evidence for the requested change.',
            'The requested field cannot be updated without in-person verification.',
            'Duplicate request already processed. Please contact support.',
            'The new value provided does not meet system requirements.',
        ];

        // ── 1. Pending patient update requests (20) ─────────────────────────
        for ($i = 0; $i < 20; $i++) {
            if ($patients->isEmpty()) {
                break;
            }
            $patient   = $patients->random();
            $field     = $patientFields[array_rand($patientFields)];
            $userId    = optional($patient->user)->id;

            $records[] = [
                'target_type'           => 'patient',
                'target_id'             => $patient->id,
                'patient_id'            => $patient->id,
                'doctor_id'             => null,
                'user_id'               => $userId,
                'requester_type'        => 'patient',
                'field_name'            => $field,
                'old_value'             => 'Old ' . ucfirst($field) . ' Value',
                'new_value'             => 'New ' . ucfirst($field) . ' Value',
                'reason'                => 'My ' . str_replace('_', ' ', $field) . ' information has changed and needs to be updated.',
                'status'                => 'pending',
                'reviewed_by'           => null,
                'reviewed_at'           => null,
                'reviewer_notes'        => null,
                'cancelled_by'          => null,
                'cancelled_at'          => null,
                'cancellation_reason'   => null,
                'created_at'            => $now->copy()->subDays(rand(1, 30))->toDateTimeString(),
                'updated_at'            => $now->toDateTimeString(),
                'deleted_at'            => null,
            ];
        }

        // ── 2. Approved patient update requests (15) ────────────────────────
        for ($i = 0; $i < 15; $i++) {
            if ($patients->isEmpty()) {
                break;
            }
            $patient     = $patients->random();
            $field       = $patientFields[array_rand($patientFields)];
            $userId      = optional($patient->user)->id;
            $reviewedAt  = $now->copy()->subDays(rand(1, 14));

            $records[] = [
                'target_type'           => 'patient',
                'target_id'             => $patient->id,
                'patient_id'            => $patient->id,
                'doctor_id'             => null,
                'user_id'               => $userId,
                'requester_type'        => 'patient',
                'field_name'            => $field,
                'old_value'             => 'Old ' . ucfirst($field) . ' Value',
                'new_value'             => 'Updated ' . ucfirst($field) . ' Value',
                'reason'                => 'Updating ' . str_replace('_', ' ', $field) . ' to reflect current information.',
                'status'                => 'approved',
                'reviewed_by'           => $adminId,
                'reviewed_at'           => $reviewedAt->toDateTimeString(),
                'reviewer_notes'        => 'Approved after verification.',
                'cancelled_by'          => null,
                'cancelled_at'          => null,
                'cancellation_reason'   => null,
                'created_at'            => $reviewedAt->copy()->subDays(rand(1, 7))->toDateTimeString(),
                'updated_at'            => $now->toDateTimeString(),
                'deleted_at'            => null,
            ];
        }

        // ── 3. Rejected update requests (10) ────────────────────────────────
        for ($i = 0; $i < 10; $i++) {
            if ($patients->isEmpty()) {
                break;
            }
            $patient     = $patients->random();
            $field       = $patientFields[array_rand($patientFields)];
            $userId      = optional($patient->user)->id;
            $reviewedAt  = $now->copy()->subDays(rand(1, 20));

            $records[] = [
                'target_type'           => 'patient',
                'target_id'             => $patient->id,
                'patient_id'            => $patient->id,
                'doctor_id'             => null,
                'user_id'               => $userId,
                'requester_type'        => 'patient',
                'field_name'            => $field,
                'old_value'             => 'Old ' . ucfirst($field) . ' Value',
                'new_value'             => 'Requested ' . ucfirst($field) . ' Value',
                'reason'                => 'Attempting to update ' . str_replace('_', ' ', $field) . '.',
                'status'                => 'rejected',
                'reviewed_by'           => $adminId,
                'reviewed_at'           => $reviewedAt->toDateTimeString(),
                'reviewer_notes'        => $rejectionNotes[array_rand($rejectionNotes)],
                'cancelled_by'          => null,
                'cancelled_at'          => null,
                'cancellation_reason'   => null,
                'created_at'            => $reviewedAt->copy()->subDays(rand(1, 7))->toDateTimeString(),
                'updated_at'            => $now->toDateTimeString(),
                'deleted_at'            => null,
            ];
        }

        // ── 4. Cancelled requests (8) ────────────────────────────────────────
        for ($i = 0; $i < 8; $i++) {
            if ($patients->isEmpty()) {
                break;
            }
            $patient      = $patients->random();
            $field        = $patientFields[array_rand($patientFields)];
            $userId       = optional($patient->user)->id;
            $cancelledAt  = $now->copy()->subDays(rand(1, 15));

            $records[] = [
                'target_type'           => 'patient',
                'target_id'             => $patient->id,
                'patient_id'            => $patient->id,
                'doctor_id'             => null,
                'user_id'               => $userId,
                'requester_type'        => 'patient',
                'field_name'            => $field,
                'old_value'             => 'Old ' . ucfirst($field) . ' Value',
                'new_value'             => 'Cancelled ' . ucfirst($field) . ' Value',
                'reason'                => 'Requested update for ' . str_replace('_', ' ', $field) . '.',
                'status'                => 'cancelled',
                'reviewed_by'           => null,
                'reviewed_at'           => null,
                'reviewer_notes'        => null,
                'cancelled_by'          => $userId,
                'cancelled_at'          => $cancelledAt->toDateTimeString(),
                'cancellation_reason'   => 'Changed my mind.',
                'created_at'            => $cancelledAt->copy()->subDays(rand(1, 5))->toDateTimeString(),
                'updated_at'            => $now->toDateTimeString(),
                'deleted_at'            => null,
            ];
        }

        // ── 5. Doctor update requests (10, mix of statuses) ─────────────────
        $doctorStatusPool = ['pending', 'pending', 'pending', 'approved', 'approved', 'approved', 'rejected', 'rejected', 'pending', 'approved'];

        for ($i = 0; $i < 10; $i++) {
            if ($doctors->isEmpty()) {
                break;
            }
            $doctor   = $doctors->random();
            $field    = $doctorFields[array_rand($doctorFields)];
            $userId   = optional($doctor->user)->id;
            $status   = $doctorStatusPool[$i];

            $reviewedAt   = null;
            $reviewedBy   = null;
            $reviewNotes  = null;

            if (in_array($status, ['approved', 'rejected'])) {
                $reviewedAt  = $now->copy()->subDays(rand(1, 14))->toDateTimeString();
                $reviewedBy  = $adminId;
                $reviewNotes = $status === 'approved'
                    ? 'Approved after verification.'
                    : $rejectionNotes[array_rand($rejectionNotes)];
            }

            $records[] = [
                'target_type'           => 'doctor',
                'target_id'             => $doctor->id,
                'patient_id'            => null,
                'doctor_id'             => $doctor->id,
                'user_id'               => $userId,
                'requester_type'        => 'doctor',
                'field_name'            => $field,
                'old_value'             => 'Old ' . ucfirst($field) . ' Value',
                'new_value'             => 'New ' . ucfirst($field) . ' Value',
                'reason'                => 'Updating ' . str_replace('_', ' ', $field) . ' to reflect current professional information.',
                'status'                => $status,
                'reviewed_by'           => $reviewedBy,
                'reviewed_at'           => $reviewedAt,
                'reviewer_notes'        => $reviewNotes,
                'cancelled_by'          => null,
                'cancelled_at'          => null,
                'cancellation_reason'   => null,
                'created_at'            => $now->copy()->subDays(rand(1, 30))->toDateTimeString(),
                'updated_at'            => $now->toDateTimeString(),
                'deleted_at'            => null,
            ];
        }

        if (empty($records)) {
            $this->command->warn('No update request records generated.');
            return;
        }

        DB::table('update_requests')->insert($records);

        $this->command->info('UpdateRequestSeeder: inserted ' . count($records) . ' update requests.');
    }
}
