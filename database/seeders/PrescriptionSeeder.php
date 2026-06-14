<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('prescriptions')->count() > 0) {
            $this->command->info('PrescriptionSeeder already run.');
            return;
        }

        $histories = DB::table('medical_history')
            ->select('id', 'patient_id', 'doctor_id', 'visit_date')
            ->get();

        if ($histories->isEmpty()) {
            $this->command->warn('No medical history records found. Skipping PrescriptionSeeder.');
            return;
        }

        $approvedDoctorIds = Doctor::where('is_approved', true)->pluck('id')->toArray();

        if (empty($approvedDoctorIds)) {
            $this->command->warn('No approved doctors found. Skipping PrescriptionSeeder.');
            return;
        }

        $medications = [
            ['name' => 'Amoxicillin',        'dosage' => '500mg',                    'frequency' => 'Three times daily',             'duration' => '7 days',                   'instructions' => 'Take with food. Complete the full course.'],
            ['name' => 'Lisinopril',          'dosage' => '10mg',                     'frequency' => 'Once daily',                    'duration' => 'Ongoing',                  'instructions' => 'Take in the morning. Monitor blood pressure.'],
            ['name' => 'Metformin',           'dosage' => '500mg',                    'frequency' => 'Twice daily',                   'duration' => 'Ongoing',                  'instructions' => 'Take with meals to reduce GI side effects.'],
            ['name' => 'Atorvastatin',        'dosage' => '40mg',                     'frequency' => 'Once daily at bedtime',         'duration' => 'Ongoing',                  'instructions' => 'Avoid grapefruit juice. Monitor liver enzymes.'],
            ['name' => 'Omeprazole',          'dosage' => '20mg',                     'frequency' => 'Once daily',                    'duration' => '8 weeks',                  'instructions' => 'Take 30 minutes before breakfast.'],
            ['name' => 'Salbutamol Inhaler',  'dosage' => '100mcg/puff, 2 puffs',    'frequency' => 'As needed (PRN)',               'duration' => 'Ongoing',                  'instructions' => 'Use during acute wheeze. Rinse mouth after use.'],
            ['name' => 'Metoprolol',          'dosage' => '25mg',                     'frequency' => 'Twice daily',                   'duration' => 'Ongoing',                  'instructions' => 'Do not stop suddenly. Monitor pulse.'],
            ['name' => 'Sertraline',          'dosage' => '50mg',                     'frequency' => 'Once daily in morning',         'duration' => '6 months minimum',         'instructions' => 'May take 2-4 weeks for full effect. Do not stop abruptly.'],
            ['name' => 'Levothyroxine',       'dosage' => '100mcg',                   'frequency' => 'Once daily',                    'duration' => 'Lifelong',                 'instructions' => 'Take on empty stomach, 30 minutes before food.'],
            ['name' => 'Ibuprofen',           'dosage' => '400mg',                    'frequency' => 'Three times daily with food',   'duration' => '5 days',                   'instructions' => 'Do not exceed recommended dose. Avoid in renal impairment.'],
            ['name' => 'Amlodipine',          'dosage' => '5mg',                      'frequency' => 'Once daily',                    'duration' => 'Ongoing',                  'instructions' => 'May cause ankle swelling. Monitor blood pressure.'],
            ['name' => 'Paracetamol',         'dosage' => '500mg',                    'frequency' => 'Every 6 hours as needed',       'duration' => 'PRN',                      'instructions' => 'Do not exceed 4g/day. Avoid alcohol.'],
            ['name' => 'Diclofenac',          'dosage' => '75mg',                     'frequency' => 'Twice daily with food',         'duration' => '2 weeks',                  'instructions' => 'Take with food to protect stomach.'],
            ['name' => 'Azithromycin',        'dosage' => '500mg',                    'frequency' => 'Once daily',                    'duration' => '3 days',                   'instructions' => 'Take on empty stomach. Avoid antacids.'],
            ['name' => 'Prednisolone',        'dosage' => '40mg',                     'frequency' => 'Once daily in morning',         'duration' => '5 days then taper',        'instructions' => 'Take with food. Do not stop suddenly.'],
            ['name' => 'Insulin Glargine',    'dosage' => '20 units',                 'frequency' => 'Once daily at bedtime',         'duration' => 'Ongoing',                  'instructions' => 'Rotate injection sites. Monitor blood glucose daily.'],
            ['name' => 'Warfarin',            'dosage' => '5mg',                      'frequency' => 'Once daily',                    'duration' => 'Ongoing (INR guided)',      'instructions' => 'Regular INR monitoring required. Avoid vitamin K rich foods.'],
            ['name' => 'Clopidogrel',         'dosage' => '75mg',                     'frequency' => 'Once daily',                    'duration' => '12 months',                'instructions' => 'Do not stop without consulting doctor. Report any unusual bleeding.'],
        ];

        $now = Carbon::now();
        $sixMonthsAgo = $now->copy()->subMonths(6);
        $records = [];

        foreach ($histories as $history) {
            $roll = rand(1, 100);

            // 15% chance: no prescription
            if ($roll <= 15) {
                continue;
            }

            // 60% chance: 1 prescription; 25% chance: 2-3 prescriptions
            $count = ($roll <= 75) ? 1 : rand(2, 3);

            $visitDate = Carbon::parse($history->visit_date);
            $isOld = $visitDate->lessThan($sixMonthsAgo);

            $usedMedications = [];

            for ($i = 0; $i < $count; $i++) {
                // Pick a medication not already used for this history record
                $availableMeds = array_filter($medications, fn($m) => !in_array($m['name'], $usedMedications));
                if (empty($availableMeds)) {
                    break;
                }
                $med = $availableMeds[array_rand($availableMeds)];
                $usedMedications[] = $med['name'];

                // Determine status
                if ($isOld) {
                    $statusRoll = rand(1, 100);
                    if ($statusRoll <= 40) {
                        $status = 'active';
                    } elseif ($statusRoll <= 90) {
                        $status = 'completed';
                    } else {
                        $status = 'expired';
                    }
                } else {
                    $statusRoll = rand(1, 100);
                    if ($statusRoll <= 70) {
                        $status = 'active';
                    } elseif ($statusRoll <= 90) {
                        $status = 'completed';
                    } elseif ($statusRoll <= 95) {
                        $status = 'cancelled';
                    } else {
                        $status = 'expired';
                    }
                }

                $prescribedDate = $visitDate->copy()->addDays(rand(0, 2));
                $refillsAllowed = rand(0, 3);
                $refillDate = $refillsAllowed > 0
                    ? $prescribedDate->copy()->addDays(rand(30, 90))->toDateString()
                    : null;

                $doctorId = in_array($history->doctor_id, $approvedDoctorIds)
                    ? $history->doctor_id
                    : $approvedDoctorIds[array_rand($approvedDoctorIds)];

                $records[] = [
                    'patient_id'          => $history->patient_id,
                    'doctor_id'           => $doctorId,
                    'medical_history_id'  => $history->id,
                    'medication_name'     => $med['name'],
                    'dosage'              => $med['dosage'],
                    'frequency'           => $med['frequency'],
                    'duration'            => $med['duration'],
                    'instructions'        => $med['instructions'],
                    'prescribed_date'     => $prescribedDate->toDateString(),
                    'refill_date'         => $refillDate,
                    'refills_allowed'     => $refillsAllowed,
                    'status'              => $status,
                    'created_at'          => $prescribedDate->toDateTimeString(),
                    'updated_at'          => $now->toDateTimeString(),
                    'deleted_at'          => null,
                ];
            }
        }

        if (empty($records)) {
            $this->command->warn('No prescriptions generated.');
            return;
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('prescriptions')->insert($chunk);
        }

        $this->command->info('PrescriptionSeeder: inserted ' . count($records) . ' prescriptions.');
    }
}
