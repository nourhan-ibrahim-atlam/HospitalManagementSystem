<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run in order - each seeder checks if already run (idempotent).
     * Password for all generated accounts: password123
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║     Elhaqni Development Database Seeder      ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->info('');

        $this->call([
            AdminSeeder::class,
            DoctorSeeder::class,
            PatientSeeder::class,
            EmergencyVisitSeeder::class,
            MedicalHistorySeeder::class,
            PrescriptionSeeder::class,
            NotificationSeeder::class,
            UpdateRequestSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✅ All seeders completed successfully!');
        $this->command->info('');
        $this->command->info('Test credentials (all passwords: password123):');
        $this->command->info('  Admin:   national_id=0000000000');
        $this->command->info('  Admin 2: national_id=10000000000001');
        $this->command->info('  Doctor:  national_id=1234567890 (existing approved doctor)');
        $this->command->info('  Doctor:  national_id=20000000000001 (seeded approved)');
        $this->command->info('  Patient: national_id=9876543210 (existing)');
        $this->command->info('  Patient: national_id=30000000000001 (seeded)');
        $this->command->info('');
    }
}
