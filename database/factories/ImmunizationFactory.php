<?php

namespace Database\Factories;

use App\Models\Immunization;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImmunizationFactory extends Factory
{
    protected $model = Immunization::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'vaccine_name' => $this->faker->randomElement([
                'COVID-19 Vaccine',
                'Influenza',
                'MMR',
                'Hepatitis B',
                'Tdap',
                'HPV',
                'Pneumococcal',
                'Varicella'
            ]),
            'administration_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'next_dose_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'lot_number' => $this->faker->optional()->bothify('LOT-#####'),
            'manufacturer' => $this->faker->randomElement(['Pfizer', 'Moderna', 'Johnson & Johnson', 'Sanofi', 'Merck']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
