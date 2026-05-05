<?php

namespace Database\Factories;

use App\Models\Treatment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\MedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreatmentFactory extends Factory
{
    protected $model = Treatment::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'medical_history_id' => MedicalHistory::factory(),
            'treatment_type' => $this->faker->randomElement(['Medication', 'Surgery', 'Therapy', 'Physical Therapy', 'Radiation']),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'start_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'end_date' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
            'status' => $this->faker->randomElement(['planned', 'in_progress', 'completed', 'discontinued']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
