<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmergencyVisitSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('emergency_visits')->count() > 0) {
            $this->command->info('EmergencyVisitSeeder already run. Skipping.');
            return;
        }

        $approvedDoctors = Doctor::where('is_approved', true)->pluck('id')->toArray();
        $allPatients     = Patient::pluck('id')->toArray();

        if (empty($approvedDoctors) || empty($allPatients)) {
            $this->command->warn('No approved doctors or patients found. Run DoctorSeeder and PatientSeeder first.');
            return;
        }

        $noteTemplates = [
            'Patient presented with sudden onset chest pain radiating to left arm. ECG performed, troponin levels elevated.',
            'Brought in by ambulance following RTA. Multiple lacerations, suspected fracture of right femur.',
            'Severe allergic reaction following bee sting. Administered epinephrine, patient stabilized.',
            'Diabetic patient found unconscious at home. Blood glucose 38 mg/dL on arrival.',
            'Hypertensive crisis - BP 210/130. IV labetalol administered, transferred to ICU.',
            'Pediatric patient with febrile convulsion. Temperature 40.2°C. Cooled and diazepam administered.',
            'Acute appendicitis confirmed on CT. Surgical consult called.',
            'Stroke symptoms: facial droop, arm weakness, speech difficulty. tPA administered within window.',
            'Asthma exacerbation, O2 saturation 88% on arrival. Nebulization and steroids given.',
            'Fracture of right wrist following fall. X-ray confirmed, plaster cast applied.',
            'Acute MI confirmed. Cathlab activated, patient transferred for PCI.',
            'Poisoning - ingested unknown substance. Activated charcoal administered, poison control contacted.',
            'Third-degree burns to 15% BSA following kitchen fire. Fluid resuscitation initiated.',
            'Pneumonia with sepsis. Blood cultures drawn, broad-spectrum antibiotics started.',
            'Psychiatric emergency - acute psychosis. Patient restrained safely, haloperidol administered.',
        ];

        $allVisits = [];
        $now       = now()->toDateTimeString();

        // Weighted severity: 10% critical, 20% high, 40% medium, 30% low
        $pickSeverity = function (): string {
            $r = rand(1, 100);
            if ($r <= 10) return 'critical';
            if ($r <= 30) return 'high';
            if ($r <= 70) return 'medium';
            return 'low';
        };

        $makeRow = function (
            int    $pid,
            int    $did,
            Carbon $time,
            string $severity = null,
            string $note = null
        ) use ($noteTemplates, $pickSeverity, $now): array {
            return [
                'patient_id' => $pid,
                'doctor_id'  => $did,
                'visit_time' => $time->toDateTimeString(),
                'notes'      => $note ?? $noteTemplates[array_rand($noteTemplates)],
                'severity'   => $severity ?? $pickSeverity(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        // Convenience pickers
        $rp = fn (): int => $allPatients[array_rand($allPatients)];
        $rd = fn (): int => $approvedDoctors[array_rand($approvedDoctors)];

        // ─── Group 1: Today's visits (15) ───────────────────────────────────────
        for ($i = 0; $i < 15; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::today()->addHours(rand(0, 22))->addMinutes(rand(0, 59))
            );
        }

        // ─── Group 2: This week (50) ─────────────────────────────────────────────
        for ($i = 0; $i < 50; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::now()->subDays(rand(1, 6))->addHours(rand(0, 23))
            );
        }

        // ─── Group 3: This month (80) ────────────────────────────────────────────
        for ($i = 0; $i < 80; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::now()->subDays(rand(7, 29))
            );
        }

        // ─── Group 4: Past 3 months (150) ───────────────────────────────────────
        for ($i = 0; $i < 150; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::now()->subDays(rand(30, 90))
            );
        }

        // ─── Group 5: Past year (200) ────────────────────────────────────────────
        for ($i = 0; $i < 200; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::now()->subDays(rand(91, 365))
            );
        }

        // ─── Group 6: ~2 years ago (100) ─────────────────────────────────────────
        for ($i = 0; $i < 100; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::now()->subDays(rand(366, 730))
            );
        }

        // ─── Group 7: Super-doctor – 500 visits for one doctor ───────────────────
        $superDoctor = Doctor::where('is_approved', true)->first();
        if ($superDoctor) {
            $superDoctorSeverities = ['critical', 'high', 'high', 'medium', 'medium', 'medium', 'low', 'low'];
            for ($i = 0; $i < 500; $i++) {
                $allVisits[] = $makeRow(
                    $rp(),
                    $superDoctor->id,
                    Carbon::now()->subDays(rand(0, 730))->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                    $superDoctorSeverities[array_rand($superDoctorSeverities)]
                );
            }
            $this->command->info("Super-doctor (ID: {$superDoctor->id}) assigned 500 visits.");
        }

        // ─── Group 8: Super-patient – 55 visits for one patient ──────────────────
        $superPatient = Patient::orderBy('id')->skip(50)->first();
        if ($superPatient) {
            for ($i = 0; $i < 55; $i++) {
                $allVisits[] = $makeRow(
                    $superPatient->id,
                    $rd(),
                    Carbon::now()->subDays(rand(0, 730))->addHours(rand(0, 23))->addMinutes(rand(0, 59))
                );
            }
            $this->command->info("Super-patient (ID: {$superPatient->id}) assigned 55 visits.");
        }

        // ─── Group 9: High-severity cluster (today / this week) ──────────────────
        // 30 critical
        for ($i = 0; $i < 30; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::now()->subDays(rand(0, 7))->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                'critical'
            );
        }
        // 40 high
        for ($i = 0; $i < 40; $i++) {
            $allVisits[] = $makeRow(
                $rp(), $rd(),
                Carbon::now()->subDays(rand(0, 7))->addHours(rand(0, 23))->addMinutes(rand(0, 59)),
                'high'
            );
        }

        // ─── Bulk insert in chunks of 100 ────────────────────────────────────────
        $total  = count($allVisits);
        $chunks = array_chunk($allVisits, 100);
        foreach ($chunks as $chunk) {
            DB::table('emergency_visits')->insert($chunk);
        }

        $this->command->info("EmergencyVisitSeeder completed. Total visits inserted: {$total}");
    }
}
