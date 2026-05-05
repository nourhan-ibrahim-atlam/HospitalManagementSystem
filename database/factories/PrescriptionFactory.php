<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\MedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'medical_history_id' => MedicalHistory::factory(),
            'medication_name' => $this->faker->randomElement([
                'Amoxicillin', 'Lisinopril', 'Metformin', 'Atorvastatin', 'Levothyroxine',
                'Albuterol', 'Omeprazole', 'Sertraline', 'Gabapentin', 'Hydrochlorothiazide'
            ]),
            'dosage' => $this->faker->randomElement(['500mg', '250mg', '100mg', '50mg', '25mg', '10mg']),
            'frequency' => $this->faker->randomElement(['Once daily', 'Twice daily', 'Three times daily', 'Every 8 hours', 'As needed']),
            'duration' => $this->faker->randomElement(['7 days', '14 days', '30 days', '90 days', '6 months']),
            'instructions' => $this->faker->sentence(),
            'prescribed_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'refill_date' => $this->faker->optional()->dateTimeBetween('now', '+3 months'),
            'refills_allowed' => $this->faker->numberBetween(0, 5),
            'status' => $this->faker->randomElement(['active', 'completed', 'cancelled', 'expired']),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }
}
