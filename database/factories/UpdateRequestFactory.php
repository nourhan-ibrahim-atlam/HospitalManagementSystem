<?php
// database/factories/UpdateRequestFactory.php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\UpdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpdateRequest>
 */
class UpdateRequestFactory extends Factory
{
    protected $model = UpdateRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default to patient request
        return $this->getPatientRequestDefinition();
    }

    /**
     * Get patient request definition
     */
    protected function getPatientRequestDefinition(): array
    {
        $field = $this->faker->randomElement([
            'blood_type',
            'emergency_contact',
            'phone',
            'address',
            'fname',
            'lname'
        ]);

        $patient = Patient::factory()->create();
        $requesterType = $this->faker->randomElement(['patient', 'doctor']);

        $requesterId = null;
        if ($requesterType === 'patient') {
            $requesterId = $patient->user_id;
        } else {
            $requesterId = Doctor::factory()->create()->user_id;
        }

        return [
            // Target information
            'target_type' => 'patient',
            'target_id' => $patient->id,
            'patient_id' => $patient->id,
            'doctor_id' => null,

            // Requester information
            'user_id' => $requesterId,
            'requester_type' => $requesterType,

            // Update details
            'field_name' => $field,
            'old_value' => $this->getOldValue($field, 'patient'),
            'new_value' => $this->getNewValue($field, 'patient'),
            'reason' => $this->faker->optional(0.7)->sentence(),

            // Status
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reviewer_notes' => null,

            // Cancellation
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    /**
     * Get doctor request definition
     */
    protected function getDoctorRequestDefinition(): array
    {
        $field = $this->faker->randomElement([
            'specialization',
            'phone',
            'office_address',
            'consultation_fee',
            'fname',
            'lname'
        ]);

        $doctor = Doctor::factory()->create();
        $requesterType = 'doctor';
        $requesterId = $doctor->user_id;

        return [
            // Target information
            'target_type' => 'doctor',
            'target_id' => $doctor->id,
            'patient_id' => null,
            'doctor_id' => $doctor->id,

            // Requester information
            'user_id' => $requesterId,
            'requester_type' => $requesterType,

            // Update details
            'field_name' => $field,
            'old_value' => $this->getOldValue($field, 'doctor'),
            'new_value' => $this->getNewValue($field, 'doctor'),
            'reason' => $this->faker->optional(0.7)->sentence(),

            // Status
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reviewer_notes' => null,

            // Cancellation
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    /**
     * State: Patient update request
     */
    public function forPatient(): static
    {
        return $this->state(fn() => $this->getPatientRequestDefinition());
    }

    /**
     * State: Doctor update request
     */
    public function forDoctor(): static
    {
        return $this->state(fn() => $this->getDoctorRequestDefinition());
    }

    /**
     * State: Approved request
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory()->admin()->create()->id,
            'reviewed_at' => now(),
            'reviewer_notes' => $this->faker->optional(0.5)->sentence(),
        ]);
    }

    /**
     * State: Rejected request
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'reviewed_by' => User::factory()->admin()->create()->id,
            'reviewed_at' => now(),
            'reviewer_notes' => $this->faker->sentence(),
        ]);
    }

    /**
     * State: Cancelled request
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
            'cancelled_by' => $attributes['user_id'] ?? User::factory()->create()->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $this->faker->optional(0.7)->sentence(),
        ]);
    }

    /**
     * State: Pending request
     */
    public function pending(): static
    {
        return $this->state(fn() => [
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    /**
     * Get old value for the field
     */
    protected function getOldValue(string $field, string $targetType): ?string
    {
        if ($targetType === 'patient') {
            return match($field) {
                'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-']),
                'emergency_contact' => $this->faker->numerify('+201#########'),
                'phone' => $this->faker->numerify('+201#########'),
                'address' => $this->faker->address(),
                'fname' => $this->faker->firstName(),
                'lname' => $this->faker->lastName(),
                default => $this->faker->word(),
            };
        } else {
            return match($field) {
                'specialization' => $this->faker->randomElement(['Cardiology', 'Neurology', 'Pediatrics']),
                'phone' => $this->faker->numerify('+201#########'),
                'office_address' => $this->faker->address(),
                'consultation_fee' => (string) $this->faker->numberBetween(100, 500),
                'fname' => $this->faker->firstName(),
                'lname' => $this->faker->lastName(),
                default => $this->faker->word(),
            };
        }
    }

    /**
     * Get new value for the field
     */
    protected function getNewValue(string $field, string $targetType): string
    {
        if ($targetType === 'patient') {
            return match($field) {
                'blood_type' => $this->faker->randomElement(['AB+', 'AB-', 'O+', 'O-']),
                'emergency_contact' => $this->faker->numerify('+201########'),
                'phone' => $this->faker->numerify('+201########'),
                'address' => $this->faker->address(),
                'fname' => $this->faker->firstName(),
                'lname' => $this->faker->lastName(),
                default => $this->faker->word(),
            };
        } else {
            return match($field) {
                'specialization' => $this->faker->randomElement(['Dermatology', 'Ophthalmology', 'Orthopedics']),
                'phone' => $this->faker->numerify('+201########'),
                'office_address' => $this->faker->address(),
                'consultation_fee' => (string) $this->faker->numberBetween(200, 800),
                'fname' => $this->faker->firstName(),
                'lname' => $this->faker->lastName(),
                default => $this->faker->word(),
            };
        }
    }
}
