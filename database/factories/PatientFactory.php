<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->patient()->create()->id,
            'blood_type'        => $this->faker->randomElement(
                ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
            ),
            'emergency_contact' => $this->faker->numerify('+201#########'),

        ];
    }
}