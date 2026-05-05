<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\EmergencyVisit;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyVisit>
 */
class EmergencyVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id'  => Doctor::factory(),
            'visit_time' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes'      => $this->faker->paragraph(),
            'severity'   => $this->faker->randomElement(
                             ['low','medium','high','critical']),

        ];
    }
    public function critical(): static
    {
        return $this->state(['severity' => 'critical']);
    }
}