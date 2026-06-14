<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed 3 admin accounts with predictable, idempotent identifiers.
     *
     * Re-running this seeder is safe: each admin is guarded by a
     * national_id existence check so no duplicate rows are ever created.
     *
     * Credentials for all three accounts:  password = password123
     *
     * To locate these accounts from other seeders:
     *   User::where('role','admin')->get()
     */
    public function run(): void
    {
        $admins = [
            [
                'national_id'   => '10000000000001',
                'phone'         => '+201001000001',
                'email'         => 'admin.nour@elhaqni.local',
                'fname'         => 'Nour',
                'lname'         => 'Hassan',
                'gender'        => 'female',
                'date_of_birth' => '1988-03-15',
            ],
            [
                'national_id'   => '10000000000002',
                'phone'         => '+201001000002',
                'email'         => 'admin.omar@elhaqni.local',
                'fname'         => 'Omar',
                'lname'         => 'Farouk',
                'gender'        => 'male',
                'date_of_birth' => '1985-07-22',
            ],
            [
                'national_id'   => '10000000000003',
                'phone'         => '+201001000003',
                'email'         => 'admin.sara@elhaqni.local',
                'fname'         => 'Sara',
                'lname'         => 'Mansour',
                'gender'        => 'female',
                'date_of_birth' => '1990-11-08',
            ],
        ];

        foreach ($admins as $admin) {
            if (User::where('national_id', $admin['national_id'])->exists()) {
                $this->command->info("  [SKIP] Admin [{$admin['email']}] already exists.");
                continue;
            }

            User::create([
                'fname'             => $admin['fname'],
                'lname'             => $admin['lname'],
                'national_id'       => $admin['national_id'],
                'phone'             => $admin['phone'],
                'email'             => $admin['email'],
                'password'          => Hash::make('password123'),
                'role'              => 'admin',
                'gender'            => $admin['gender'],
                'date_of_birth'     => $admin['date_of_birth'],
                'address'           => 'Cairo, Egypt',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'profile_image'     => 'profiles/DAdmin.png',
            ]);

            $this->command->info("  [OK]   Admin [{$admin['email']}] created.");
        }

        $this->command->info('AdminSeeder completed.');
    }
}
