<?php

namespace Database\Factories;

use App\Models\Allergy;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class AllergyFactory extends Factory
{
    protected $model = Allergy::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'allergen' => $this->faker->randomElement([
                'Penicillin', 'Sulfa drugs', 'Aspirin', 'Ibuprofen',
                'Peanuts', 'Shellfish', 'Latex', 'Pollen', 'Dust mites',
                'Mold', 'Pet dander', 'Eggs', 'Milk', 'Soy'
            ]),
            'reaction' => $this->faker->randomElement([
                'Rash', 'Hives', 'Swelling', 'Difficulty breathing',
                'Anaphylaxis', 'Nausea', 'Headache', 'Itching'
            ]),
            'severity' => $this->faker->randomElement(['mild', 'moderate', 'severe', 'life_threatening']),
            'diagnosed_date' => $this->faker->optional()->dateTimeBetween('-5 years', 'now'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function severe()
    {
        return $this->state(function (array $attributes) {
            return [
                'severity' => 'severe',
                'reaction' => 'Anaphylaxis',
            ];
        });
    }
}
