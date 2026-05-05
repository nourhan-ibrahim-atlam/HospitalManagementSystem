<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\MedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosisFactory extends Factory
{
    protected $model = Diagnosis::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'medical_history_id' => MedicalHistory::factory(),
            'icd_code' => $this->faker->regexify('[A-Z][0-9]{2}\.[0-9]{1,2}'),
            'diagnosis_name' => $this->faker->randomElement([
                'Essential Hypertension',
                'Type 2 Diabetes Mellitus',
                'Acute Bronchitis',
                'Urinary Tract Infection',
                'Major Depressive Disorder',
                'Osteoarthritis',
                'Asthma',
                'Hyperlipidemia'
            ]),
            'description' => $this->faker->paragraph(),
            'certainty' => $this->faker->randomElement(['confirmed', 'probable', 'possible', 'ruled_out']),
            'diagnosis_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
