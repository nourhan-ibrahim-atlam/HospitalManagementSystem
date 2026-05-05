<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\EmergencyVisit;
use App\Models\FingerPrintSimulation;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\UpdateRequest;
use App\Models\User;
use App\Models\LabTest;
use App\Models\BloodTestParameter;
use App\Models\Treatment;
use App\Models\Prescription;
use App\Models\Diagnosis;
use App\Models\VitalSign;
use App\Models\Immunization;
use App\Models\Allergy;
use App\Models\Surgery;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::factory()->admin()->create([
            'national_id' => '12345678912345',
            'fname' => "Zaid",
            'lname' => "Mohammed",
            "email" => "ZaidMo2003@gmail.com",
            'phone' => '+201064922104',
            'password' => bcrypt('admin123'),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);


        // Create 20 doctors
        $doctors = Doctor::factory(20)->create();


        Patient::factory(100)->create()->each(function (Patient $patient) use ($doctors) {

            $medicalHistories = MedicalHistory::factory(rand(1, 5))
                ->create(['patient_id' => $patient->id])
                ->each(function ($medicalHistory) use ($patient, $doctors) {


                    $labTests = LabTest::factory(rand(2, 5))
                        ->create([
                            'patient_id' => $patient->id,
                            'doctor_id' => $doctors->random()->id,
                            'medical_history_id' => $medicalHistory->id
                        ])
                        ->each(function ($labTest) {

                            BloodTestParameter::factory(rand(3, 8))
                                ->create(['lab_test_id' => $labTest->id]);
                        });

                    Treatment::factory(rand(1, 3))
                        ->create([
                            'patient_id' => $patient->id,
                            'doctor_id' => $doctors->random()->id,
                            'medical_history_id' => $medicalHistory->id
                        ]);


                    Prescription::factory(rand(1, 2))
                        ->create([
                            'patient_id' => $patient->id,
                            'doctor_id' => $doctors->random()->id,
                            'medical_history_id' => $medicalHistory->id
                        ]);


                    Diagnosis::factory(rand(1, 2))
                        ->create([
                            'patient_id' => $patient->id,
                            'doctor_id' => $doctors->random()->id,
                            'medical_history_id' => $medicalHistory->id
                        ]);

                    VitalSign::factory()
                        ->create([
                            'patient_id' => $patient->id,
                            'medical_history_id' => $medicalHistory->id
                        ]);
                });

            UpdateRequest::factory(rand(0, 2))
                ->create(['patient_id' => $patient->id]);

            UpdateRequest::factory(rand(0, 2))
                ->approved()
                ->create([
                    'patient_id' => $patient->id,
                    'reviewed_by' => $doctors->random()->id,
                ]);


            $visits = rand(0, 5);
            if ($visits > 0) {
                EmergencyVisit::factory($visits)->create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctors->random()->id,
                ]);
            }


            FingerPrintSimulation::factory()->create(['patient_id' => $patient->id]);


            Immunization::factory(rand(2, 6))
                ->create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctors->random()->id,
                ]);


            Allergy::factory(rand(1, 4))
                ->create(['patient_id' => $patient->id]);

            if (rand(1, 100) <= 30) {
                Surgery::factory(rand(0, 2))
                    ->create(['patient_id' => $patient->id]);
            }
        });
    }
}
