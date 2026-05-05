<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         $role = $this->faker->randomElement(['patient', 'doctor']);
        return [
            'fname'        => $this->faker->name(),
            'lname'        => $this->faker->name(),
            'email'        => $this->faker->unique()->email(),
            'national_id' => $this->faker->unique()->numerify('##############'),
            'phone'       => $this->faker->unique()->numerify('+201#########'),
            'password'    => bcrypt('password'),
            'role'        =>  $role,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'date_of_birth' => $this->faker->date(),
            'address' => $this->faker->address(),
            'profile_image' => match ($role) {
            'doctor'  => 'images/default-doctor.png',
            'patient' => 'images/default-patient.png',
            'admin'   => 'images/default-admin.png',
        },
            'phone_verified_at' => $this->faker->boolean(80)? now(): null,
            'email_verified_at' => $this->faker->boolean(80)? now(): null,

        ];
    }
    public function patient(): static
    {
        return $this->state(['role' => 'patient']);
    }
    public function doctor(): static
    {
        return $this->state(['role' => 'doctor']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }
    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
    public function withDefaultImage(): static
    {
        return $this->afterCreating(function (User $user) {
        });
    }
}
