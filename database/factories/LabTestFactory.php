<?php

namespace Database\Factories;

use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\MedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabTestFactory extends Factory
{
    protected $model = LabTest::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'medical_history_id' => MedicalHistory::factory(),
            'test_name' => $this->faker->randomElement([
                'Complete Blood Count',
                'Basic Metabolic Panel',
                'Lipid Panel',
                'Liver Function Test',
                'Thyroid Function Test',
                'Urinalysis',
                'COVID-19 PCR',
                'Blood Glucose'
            ]),
            'test_category' => $this->faker->randomElement(['Blood', 'Urine', 'Imaging', 'Molecular']),
            'test_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'result_date' => $this->faker->optional()->dateTimeBetween('-5 months', 'now'),
            'results' => $this->faker->optional()->paragraph(),
            'reference_range' => $this->faker->optional()->sentence(),
            'interpretation' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'file_path' => $this->faker->optional()->filePath(),
            'technician_notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'result_date' => now(),
            ];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'result_date' => null,
            ];
        });
    }
}
