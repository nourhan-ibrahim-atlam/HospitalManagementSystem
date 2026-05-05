<?php

namespace Database\Factories;

use App\Models\Surgery;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurgeryFactory extends Factory
{
    protected $model = Surgery::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'surgery_name' => $this->faker->randomElement([
                'Appendectomy',
                'Cholecystectomy',
                'Hernia Repair',
                'Knee Replacement',
                'Hip Replacement',
                'Cataract Surgery',
                'Cardiac Bypass',
                'Tonsillectomy',
                'C-Section',
                'Tumor Removal'
            ]),
            'surgery_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'hospital' => $this->faker->company() . ' Hospital',
            'surgeon_name' => $this->faker->name(),
            'reason' => $this->faker->sentence(),
            'complications' => $this->faker->optional()->sentence(),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
