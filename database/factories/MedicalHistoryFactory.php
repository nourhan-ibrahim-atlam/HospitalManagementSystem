<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalHistory>
 */
class MedicalHistoryFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = MedicalHistory::class;
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'visit_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'chief_complaint' => $this->faker->sentence(),
            'present_illness_history' => $this->faker->paragraph(),
            'past_medical_history' => $this->faker->optional()->paragraph(),
            'family_history' => $this->faker->optional()->paragraph(),
            'social_history' => $this->faker->optional()->sentence(),
            'allergies' => $this->faker->optional()->sentence(),
            'current_medications' => $this->faker->optional()->sentence(),
            'physical_examination' => $this->faker->paragraph(),
            'diagnosis' => $this->faker->sentence(),
            'treatment_plan' => $this->faker->paragraph(),
            'doctor_notes' => $this->faker->optional()->paragraph(),
            'visit_type' => $this->faker->randomElement(['initial', 'follow_up', 'emergency', 'consultation']),
            'status' => $this->faker->randomElement(['active', 'resolved', 'inactive']),
        ];

    }
    public function active(){
        return $this->state(['status' => 'active']) ;
    }

    public function resolved(){
        return $this->state(['status' => 'resolved']) ;
    }
}
