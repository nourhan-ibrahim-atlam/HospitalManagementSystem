<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    private static $specs = [
        'General Practitioner',
        'Cardiology',
        'Dermatology',
        'Neurology',
        'Pediatrics',
        'Orthopedics',
        'Gynecology',
        'Ophthalmology',
        'ENT',
        'Urology',
        'Psychiatry',
        'Oncology',
        'Radiology',
        'Anesthesiology',
        'Gastroenterology',
        'Endocrinology',
        'Pulmonology',
        'Nephrology',
        'Hematology',
        'Infectious Diseases',
        'Rheumatology',
        'Plastic Surgery',
        'Emergency Medicine',
        'Family Medicine'
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor()->create()->id,
            'specialization' => $this->faker->randomElement(self::$specs),
            'medical_license' => 'doctor-documents/sample-license-' . $this->faker->uuid() . '.pdf',
            'degree_certificate' => 'doctor-documents/sample-degree-' . $this->faker->uuid() . '.pdf',
            'professional_id_card' => 'doctor-documents/sample-id-' . $this->faker->uuid() . '.jpg',
            'is_approved' => $this->faker->boolean(80),
            'approved_at' => $this->faker->optional(0.8)->boolean(50) ? now() : null,
            'approved_by' => $this->faker->optional(0.8)->randomElement([1, 2, 3]),
            'rejection_reason' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the doctor is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the doctor is not approved (pending).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the doctor is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
            'approved_at' => null,
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Configure the factory to use specific specialization.
     */
    public function specialization(string $spec): static
    {
        return $this->state(fn (array $attributes) => [
            'specialization' => $spec,
        ]);
    }

    /**
     * Configure with sample document files.
     */
    public function withDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'medical_license' => 'doctor-documents/license-' . $this->faker->uuid() . '.pdf',
            'degree_certificate' => 'doctor-documents/degree-' . $this->faker->uuid() . '.pdf',
            'professional_id_card' => 'doctor-documents/id-' . $this->faker->uuid() . '.jpg',
        ]);
    }

    /**
     * Configure without any documents.
     */
    public function withoutDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'medical_license' => null,
            'degree_certificate' => null,
            'professional_id_card' => null,
        ]);
    }
}
