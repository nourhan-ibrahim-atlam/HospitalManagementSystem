<?php

namespace Database\Factories;

use App\Models\VitalSign;
use App\Models\Patient;
use App\Models\MedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class VitalSignFactory extends Factory
{
    protected $model = VitalSign::class;

    public function definition(): array
    {
        $height = $this->faker->numberBetween(150, 190);
        $weight = $this->faker->numberBetween(50, 100);
        $bmi = round($weight / (($height / 100) ** 2), 2);

        return [
            'patient_id' => Patient::factory(),
            'medical_history_id' => MedicalHistory::factory(),
            'temperature' => $this->faker->randomFloat(1, 36.0, 39.0),
            'heart_rate' => $this->faker->numberBetween(60, 100),
            'respiratory_rate' => $this->faker->numberBetween(12, 20),
            'blood_pressure_systolic' => $this->faker->numberBetween(90, 160),
            'blood_pressure_diastolic' => $this->faker->numberBetween(60, 100),
            'oxygen_saturation' => $this->faker->randomFloat(1, 95, 100),
            'height' => $height,
            'weight' => $weight,
            'bmi' => $bmi,
            'blood_glucose' => $this->faker->optional()->numberBetween(70, 200),
            'measured_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function abnormal()
    {
        return $this->state(function (array $attributes) {
            return [
                'blood_pressure_systolic' => $this->faker->numberBetween(160, 200),
                'blood_pressure_diastolic' => $this->faker->numberBetween(100, 120),
                'heart_rate' => $this->faker->numberBetween(120, 150),
                'temperature' => $this->faker->randomFloat(1, 38.5, 40.0),
            ];
        });
    }
}
