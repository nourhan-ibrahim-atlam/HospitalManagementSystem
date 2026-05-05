<?php

namespace Database\Factories;

use App\Models\FingerprintSimulation;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FingerprintSimulation>
 */
class FingerprintSimulationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id'       => Patient::factory(),
            'fingerprint_code' => FingerprintSimulation::generateCode(),

        ];
    }
}
