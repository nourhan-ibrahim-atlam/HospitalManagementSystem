<?php

namespace Database\Seeders;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    /**
     * Seeds 161 patients across five groups using high-performance
     * DB::table() bulk inserts (chunked arrays of 50).
     *
     * ─── Groups ──────────────────────────────────────────────────────────────
     *
     *  Group A  (i =   1–100)  100 regular patients
     *                          created_at spread over the last 2 years
     *                          ages 5–84, cycling blood types, 50/50 gender
     *
     *  Group B  (i = 101–120)   20 new patients
     *                          created_at within the last 7 days
     *                          ages 18–60
     *
     *  Group C  (i = 121–150)   30 returning patients
     *                          created_at 3–24 months ago
     *                          ages 25–75
     *
     *  Group D  (i = 151–160)   10 high-history patients
     *                          address contains '[HIGH_HISTORY]' marker so
     *                          EmergencyVisitSeeder / MedicalHistorySeeder can
     *                          locate them with:
     *                            Patient::with('user')
     *                                   ->whereHas('user', fn($q) =>
     *                                       $q->where('address','like','%[HIGH_HISTORY]%'))
     *                                   ->get()
     *
     *  Group E  (i = 161)        1 edge-case patient with extremely long name
     *
     * ─── ID ranges ───────────────────────────────────────────────────────────
     *  national_id : 30000000000001 – 30000000000161  (prefix "3", 14 chars)
     *  phone       : +201003000001  – +201003000161
     *  email       : patient.{firstname}{i}@elhaqni.local
     *  password    : password123
     *
     * ─── Idempotency ─────────────────────────────────────────────────────────
     *  Returns early if Patient rows already exist (safe to re-run after reset).
     *
     * ─── Finding groups later ────────────────────────────────────────────────
     *  New patients       : Patient::orderBy('id')->skip(100)->take(20)->get()
     *  Returning patients : Patient::orderBy('id')->skip(120)->take(30)->get()
     *  High-history       : Patient::with('user')
     *                              ->whereHas('user', fn($q) =>
     *                                  $q->where('address','like','%[HIGH_HISTORY]%'))
     *                              ->get()
     *  Edge-case patient  : User::where('national_id','30000000000161')->first()->patient
     */
    public function run(): void
    {
        if (Patient::count() > 0) {
            $this->command->info('PatientSeeder already run. Skipping.');
            return;
        }

        // ─── Name pools (intentional duplicates to exercise search) ──────────
        $firstNamesMale = [
            'Ahmed', 'Mohamed', 'Omar', 'Ali', 'Hassan',
            'Ahmed', 'Mohamed', 'Ahmed', 'Omar', 'Mohamed',
            'Youssef', 'Kareem', 'Tamer', 'Wael', 'Amr',
            'Hisham', 'Tarek', 'Sherif', 'Mostafa', 'Ayman',
            'Mahmoud', 'Khaled', 'Samir', 'Nader', 'Fares',
        ];

        $firstNamesFemale = [
            'Sara', 'Nour', 'Dina', 'Rana', 'Heba',
            'Mona', 'Rania', 'Yasmine', 'Mariam', 'Nadia',
            'Eman', 'Doaa', 'Asmaa', 'Hana', 'Salma',
            'Sara', 'Nour', 'Sara', 'Mona', 'Heba',
            'Ghada', 'Amira', 'Mai', 'Reem', 'Layla',
        ];

        $lastNames = [
            'Mohamed', 'Ahmed', 'Hassan', 'Hussein', 'Ali',
            'Mahmoud', 'Ibrahim', 'Abdel-Rahman', 'El-Sayed', 'El-Masry',
            'Khalil', 'Mansour', 'Nasser', 'Gomaa', 'Salem',
            'Mohamed', 'Ahmed', 'Hassan', 'Ali', 'Mahmoud',
            'Ibrahim', 'Salem', 'Shahin', 'Barakat', 'Zaki',
        ];

        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        $egyptianCities = [
            'Cairo', 'Alexandria', 'Giza', 'Luxor', 'Aswan',
            'Port Said', 'Suez', 'Mansoura', 'Tanta', 'Zagazig',
        ];

        $maleCount    = count($firstNamesMale);
        $femaleCount  = count($firstNamesFemale);
        $lastCount    = count($lastNames);
        $bloodCount   = count($bloodTypes);
        $cityCount    = count($egyptianCities);

        $now        = Carbon::now();
        $nowStr     = $now->toDateTimeString();
        $hashedPass = Hash::make('password123'); // hashed once; re-used for all rows

        // Helpers
        $makeNid   = static fn(int $i): string => '3' . str_pad($i, 13, '0', STR_PAD_LEFT);
        $makePhone = static fn(int $i): string => '+201003' . str_pad($i, 6, '0', STR_PAD_LEFT);

        /**
         * Build an emergency contact number that is guaranteed to differ per
         * patient and fit within the 20-char column limit.
         * Formula: '+2010' (5) + 8-digit deterministic number (8) = 13 chars.
         */
        $makeEmergency = static fn(int $i): string =>
            '+2010' . str_pad(($i * 7919) % 100_000_000, 8, '0', STR_PAD_LEFT);

        // ─────────────────────────────────────────────────────────────────────
        // Phase 1 — Build user rows for all 161 patients
        // ─────────────────────────────────────────────────────────────────────

        $nationalIds = []; // $i => national_id string (for lookup after insert)
        $userRows    = []; // flat list for DB::table('users')->insert()

        // ── Group A: 100 regular patients  (i = 1–100) ──────────────────────
        for ($i = 1; $i <= 100; $i++) {
            $isMale    = $i % 2 === 1;
            $fname     = $isMale ? $firstNamesMale[($i - 1) % $maleCount]
                                 : $firstNamesFemale[($i - 1) % $femaleCount];
            $lname     = $lastNames[($i - 1) % $lastCount];
            $gender    = $isMale ? 'male' : 'female';
            $age       = 5 + (($i - 1) % 80);          // ages 5–84
            $dob       = $now->copy()->subYears($age)->subDays(($i * 37) % 365)->toDateString();
            $nid       = $makeNid($i);
            $createdAt = $now->copy()->subDays(($i * 7) % 730)->toDateTimeString();
            $city      = $egyptianCities[($i - 1) % $cityCount];
            $slug      = strtolower(str_replace([' ', '-', "'"], '', $fname));

            $nationalIds[$i] = $nid;

            $userRows[] = [
                'fname'             => $fname,
                'lname'             => $lname,
                'national_id'       => $nid,
                'phone'             => $makePhone($i),
                'email'             => "patient.{$slug}{$i}@elhaqni.local",
                'password'          => $hashedPass,
                'role'              => 'patient',
                'gender'            => $gender,
                'date_of_birth'     => $dob,
                'address'           => "{$city}, Egypt",
                'profile_image'     => 'profiles/DPatient.png',
                'email_verified_at' => $nowStr,
                'phone_verified_at' => null,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ];
        }

        // ── Group B: 20 new patients  (i = 101–120) ─────────────────────────
        for ($i = 101; $i <= 120; $i++) {
            $j         = $i - 100;                      // local index 1–20
            $isMale    = $j % 2 === 1;
            $fname     = $isMale ? $firstNamesMale[($j - 1) % $maleCount]
                                 : $firstNamesFemale[($j - 1) % $femaleCount];
            $lname     = $lastNames[($j - 1) % $lastCount];
            $gender    = $isMale ? 'male' : 'female';
            $age       = 18 + (($j - 1) % 43);         // ages 18–60
            $dob       = $now->copy()->subYears($age)->subDays(($i * 41) % 365)->toDateString();
            $nid       = $makeNid($i);
            $createdAt = $now->copy()->subDays(($j - 1) % 7)->toDateTimeString();
            $city      = $egyptianCities[($j - 1) % $cityCount];
            $slug      = strtolower(str_replace([' ', '-', "'"], '', $fname));

            $nationalIds[$i] = $nid;

            $userRows[] = [
                'fname'             => $fname,
                'lname'             => $lname,
                'national_id'       => $nid,
                'phone'             => $makePhone($i),
                'email'             => "patient.{$slug}{$i}@elhaqni.local",
                'password'          => $hashedPass,
                'role'              => 'patient',
                'gender'            => $gender,
                'date_of_birth'     => $dob,
                'address'           => "{$city}, Egypt",
                'profile_image'     => 'profiles/DPatient.png',
                'email_verified_at' => $nowStr,
                'phone_verified_at' => null,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ];
        }

        // ── Group C: 30 returning patients  (i = 121–150) ───────────────────
        for ($i = 121; $i <= 150; $i++) {
            $j         = $i - 120;                      // local index 1–30
            $isMale    = $j % 2 === 1;
            $fname     = $isMale ? $firstNamesMale[($j - 1) % $maleCount]
                                 : $firstNamesFemale[($j - 1) % $femaleCount];
            $lname     = $lastNames[($j - 1) % $lastCount];
            $gender    = $isMale ? 'male' : 'female';
            $age       = 25 + (($j - 1) % 51);         // ages 25–75
            $dob       = $now->copy()->subYears($age)->subDays(($i * 43) % 365)->toDateString();
            $nid       = $makeNid($i);
            $monthsAgo = ($j - 1) % 22 + 3;            // 3–24 months ago
            $createdAt = $now->copy()->subMonths($monthsAgo)->toDateTimeString();
            $city      = $egyptianCities[($j - 1) % $cityCount];
            $slug      = strtolower(str_replace([' ', '-', "'"], '', $fname));

            $nationalIds[$i] = $nid;

            $userRows[] = [
                'fname'             => $fname,
                'lname'             => $lname,
                'national_id'       => $nid,
                'phone'             => $makePhone($i),
                'email'             => "patient.{$slug}{$i}@elhaqni.local",
                'password'          => $hashedPass,
                'role'              => 'patient',
                'gender'            => $gender,
                'date_of_birth'     => $dob,
                'address'           => "{$city}, Egypt",
                'profile_image'     => 'profiles/DPatient.png',
                'email_verified_at' => $nowStr,
                'phone_verified_at' => null,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ];
        }

        // ── Group D: 10 high-history patients  (i = 151–160) ────────────────
        // Address contains '[HIGH_HISTORY]' so downstream seeders can find
        // these rows with a simple LIKE query.
        for ($i = 151; $i <= 160; $i++) {
            $j         = $i - 150;                      // local index 1–10
            $isMale    = $j % 2 === 1;
            $fname     = $isMale ? $firstNamesMale[($j - 1) % $maleCount]
                                 : $firstNamesFemale[($j - 1) % $femaleCount];
            $lname     = $lastNames[($j - 1) % $lastCount];
            $gender    = $isMale ? 'male' : 'female';
            $age       = 30 + (($j - 1) % 41);         // ages 30–70
            $dob       = $now->copy()->subYears($age)->subDays(($i * 59) % 365)->toDateString();
            $nid       = $makeNid($i);
            $monthsAgo = ($j - 1) % 22 + 3;
            $createdAt = $now->copy()->subMonths($monthsAgo)->toDateTimeString();
            $city      = $egyptianCities[($j - 1) % $cityCount];
            $slug      = strtolower(str_replace([' ', '-', "'"], '', $fname));

            $nationalIds[$i] = $nid;

            $userRows[] = [
                'fname'             => $fname,
                'lname'             => $lname,
                'national_id'       => $nid,
                'phone'             => $makePhone($i),
                'email'             => "patient.{$slug}{$i}@elhaqni.local",
                'password'          => $hashedPass,
                'role'              => 'patient',
                'gender'            => $gender,
                'date_of_birth'     => $dob,
                'address'           => "{$city}, Egypt [HIGH_HISTORY]",
                'profile_image'     => 'profiles/DPatient.png',
                'email_verified_at' => $nowStr,
                'phone_verified_at' => null,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ];
        }

        // ── Group E: 1 edge-case patient with an extremely long name  (i = 161)
        $nationalIds[161] = $makeNid(161);

        $userRows[] = [
            'fname'             => 'Abdelrahman-Mostafa-Mohamed-Hussein-El-Naggar-Ibrahim',
            'lname'             => 'El-Sayed-Abdel-Rahman-Hassan-Mohamed-Ibrahim-Salem',
            'national_id'       => $makeNid(161),
            'phone'             => $makePhone(161),
            'email'             => 'patient.edgecase161@elhaqni.local',
            'password'          => $hashedPass,
            'role'              => 'patient',
            'gender'            => 'male',
            'date_of_birth'     => $now->copy()->subYears(35)->toDateString(),
            'address'           => 'Cairo, Egypt',
            'profile_image'     => 'profiles/DPatient.png',
            'email_verified_at' => $nowStr,
            'phone_verified_at' => null,
            'created_at'        => $nowStr,
            'updated_at'        => $nowStr,
        ];

        // ─────────────────────────────────────────────────────────────────────
        // Phase 2 — Bulk-insert users in chunks of 50
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('Inserting 161 user records (chunked by 50)…');

        foreach (array_chunk($userRows, 50) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        // Retrieve the auto-generated user IDs keyed by national_id.
        $userIdMap = DB::table('users')
            ->whereIn('national_id', array_values($nationalIds))
            ->pluck('id', 'national_id');

        // ─────────────────────────────────────────────────────────────────────
        // Phase 3 — Build patient rows (needs user IDs from Phase 2)
        // ─────────────────────────────────────────────────────────────────────
        $patientRows    = [];
        $userIdsIndexed = []; // $i => user_id

        for ($i = 1; $i <= 161; $i++) {
            $nid    = $nationalIds[$i];
            $userId = $userIdMap[$nid];
            $userIdsIndexed[$i] = $userId;

            // Mirror the created_at from the user row so timestamps align.
            $createdAt = $userRows[$i - 1]['created_at'];

            $patientRows[] = [
                'user_id'           => $userId,
                'blood_type'        => $bloodTypes[($i - 1) % $bloodCount],
                'emergency_contact' => $makeEmergency($i),
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ];
        }

        // ─────────────────────────────────────────────────────────────────────
        // Phase 4 — Bulk-insert patients in chunks of 50
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('Inserting 161 patient records (chunked by 50)…');

        foreach (array_chunk($patientRows, 50) as $chunk) {
            DB::table('patients')->insert($chunk);
        }

        // Retrieve the auto-generated patient IDs keyed by user_id.
        $patientIdMap = DB::table('patients')
            ->whereIn('user_id', array_values($userIdsIndexed))
            ->pluck('id', 'user_id');

        // ─────────────────────────────────────────────────────────────────────
        // Phase 5 — Build fingerprint rows (needs patient IDs from Phase 4)
        //
        // fingerprint_code uses a zero-padded $i to exactly 32 characters.
        // Format: 00000000000000000000000000000001 .. 00000000000000000000000000000161
        // These never collide with factory-generated codes (Str::upper(random(32))).
        // ─────────────────────────────────────────────────────────────────────
        $fingerprintRows = [];

        for ($i = 1; $i <= 161; $i++) {
            $userId    = $userIdsIndexed[$i];
            $patientId = $patientIdMap[$userId];

            $fingerprintRows[] = [
                'patient_id'       => $patientId,
                'fingerprint_code' => str_pad($i, 32, '0', STR_PAD_LEFT),
                'created_at'       => $nowStr,
                'updated_at'       => $nowStr,
            ];
        }

        // ─────────────────────────────────────────────────────────────────────
        // Phase 6 — Bulk-insert fingerprints in chunks of 50
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('Inserting 161 fingerprint records (chunked by 50)…');

        foreach (array_chunk($fingerprintRows, 50) as $chunk) {
            DB::table('fingerprint_simulation')->insert($chunk);
        }

        $this->command->info('PatientSeeder completed.');
        $this->command->info('  100 regular patients   (i =   1–100)  spread over last 2 years');
        $this->command->info('   20 new patients       (i = 101–120)  created within last 7 days');
        $this->command->info('   30 returning patients (i = 121–150)  created 3–24 months ago');
        $this->command->info('   10 high-history       (i = 151–160)  address contains [HIGH_HISTORY]');
        $this->command->info('    1 edge-case          (i = 161)      extremely long name');
    }
}
