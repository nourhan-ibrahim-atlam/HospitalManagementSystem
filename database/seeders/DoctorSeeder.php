<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Seeds 40 doctors across four distinct states for thorough testing:
     *
     *   Group 1  (i =  1–20)  20 approved doctors   — both verifications, full docs, is_approved=true
     *   Group 2  (i = 21–30)  10 pending doctors     — email verified, phone null, partial docs
     *   Group 3  (i = 31–35)   5 rejected doctors    — both verified, full docs, rejection_reason set
     *   Group 4  (i = 36–40)   5 soft-deleted        — approved then soft-deleted via delete()
     *
     * national_id range : 20000000000001 – 20000000000040  (prefix "2", 14 chars)
     * phone range       : +201002000001  – +201002000040
     * email pattern     : dr.{firstname}{i}@elhaqni.local
     * password          : password123
     *
     * Idempotent: returns early if any Doctor rows already exist.
     *
     * To retrieve specific groups after seeding:
     *   Doctor::approved()->with('user')->get()
     *   Doctor::pendingApproval()->with('user')->get()
     *   Doctor::where('rejection_reason','!=',null)->with('user')->get()
     *   Doctor::withTrashed()->whereNotNull('deleted_at')->with('user')->get()
     */
    public function run(): void
    {
        if (Doctor::count() > 0) {
            $this->command->info('DoctorSeeder already run. Skipping.');
            return;
        }

        // ─── Name pools ────────────────────────────────────────────────────
        $maleNames = [
            'Ahmed', 'Mohamed', 'Omar', 'Ali', 'Mahmoud',
            'Hassan', 'Ibrahim', 'Khalid', 'Youssef', 'Kareem',
            'Tamer', 'Sameh', 'Wael', 'Amr', 'Hisham',
            'Tarek', 'Sherif', 'Mostafa', 'Ayman', 'Khaled',
        ];

        $femaleNames = [
            'Sara', 'Nour', 'Dina', 'Rana', 'Heba',
            'Mona', 'Rania', 'Yasmine', 'Mariam', 'Nadia',
            'Eman', 'Doaa', 'Asmaa', 'Hana', 'Salma',
            'Ghada', 'Amira', 'Mai', 'Reem', 'Layla',
        ];

        $lastNames = [
            'Mohamed', 'Ahmed', 'Hassan', 'Hussein', 'Ali',
            'Mahmoud', 'Ibrahim', 'Abdel-Rahman', 'El-Sayed', 'El-Masry',
            'Khalil', 'Mansour', 'Nasser', 'Gomaa', 'Salem',
            'Shahin', 'Barakat', 'Zaki', 'Farouk', 'El-Naggar',
        ];

        $specs = [
            'General Practitioner', 'Cardiology', 'Dermatology', 'Neurology',
            'Pediatrics', 'Orthopedics', 'Gynecology', 'Ophthalmology',
            'ENT', 'Urology', 'Psychiatry', 'Oncology',
            'Radiology', 'Anesthesiology', 'Gastroenterology', 'Endocrinology',
            'Pulmonology', 'Nephrology', 'Hematology', 'Infectious Diseases',
            'Rheumatology', 'Plastic Surgery', 'Emergency Medicine', 'Family Medicine',
        ];

        $rejectionReasons = [
            'Medical license could not be verified with the issuing authority.',
            'Degree certificate appears to be incomplete or from an unrecognized institution.',
            'Professional ID card expired more than 6 months ago.',
            'Insufficient documentation provided for specialization claim.',
            'Background check returned inconsistencies requiring further review.',
        ];

        $now     = Carbon::now();
        $adminId = User::where('role', 'admin')->value('id') ?? 1;

        $maleCount   = count($maleNames);
        $femaleCount = count($femaleNames);
        $lastCount   = count($lastNames);
        $specCount   = count($specs);

        // ─── Helpers ────────────────────────────────────────────────────────

        /** Build a 14-digit national_id with prefix "2" for doctors. */
        $nid = static fn(int $i): string => '2' . str_pad($i, 13, '0', STR_PAD_LEFT);

        /** Egyptian mobile number in the +201002xxxxxx range. */
        $phone = static fn(int $i): string => '+201002' . str_pad($i, 6, '0', STR_PAD_LEFT);

        /** doctor-documents path, zero-padded 3-digit suffix. */
        $docPath = static fn(string $type, int $i): string =>
            'doctor-documents/' . $type . '_' . str_pad($i, 3, '0', STR_PAD_LEFT) . '.pdf';

        /**
         * Resolve first name, last name, gender, and date-of-birth for index $i.
         * Odd indices → male, even → female. Ages cycle 30–60.
         */
        $meta = function (int $i, int $seed) use (
            $maleNames, $femaleNames, $lastNames,
            $maleCount, $femaleCount, $lastCount, $now
        ): array {
            $isMale = $i % 2 === 1;
            $fname  = $isMale
                ? $maleNames[($i - 1) % $maleCount]
                : $femaleNames[($i - 1) % $femaleCount];
            $lname  = $lastNames[($i - 1) % $lastCount];
            $gender = $isMale ? 'male' : 'female';
            $dob    = $now->copy()
                ->subYears(30 + (($i - 1) % 31))
                ->subDays(($i * $seed) % 365)
                ->toDateString();

            return [$fname, $lname, $gender, $dob];
        };

        /**
         * Persist a User row and return the model.
         */
        $makeUser = function (
            int $i,
            string $fname,
            string $lname,
            string $gender,
            string $dob,
            ?string $address,
            bool $phoneVerified
        ) use ($nid, $phone, $now): User {
            $slug = strtolower(str_replace([' ', '-', "'"], '', $fname));

            return User::create([
                'fname'             => $fname,
                'lname'             => $lname,
                'national_id'       => $nid($i),
                'phone'             => $phone($i),
                'email'             => "dr.{$slug}{$i}@elhaqni.local",
                'password'          => Hash::make('password123'),
                'role'              => 'doctor',
                'gender'            => $gender,
                'date_of_birth'     => $dob,
                'address'           => $address,
                'email_verified_at' => $now,
                'phone_verified_at' => $phoneVerified ? $now : null,
                'profile_image'     => 'profiles/DDoctor.png',
            ]);
        };

        // ─────────────────────────────────────────────────────────────────────
        // GROUP 1: 20 Approved Doctors  (i = 1 – 20)
        // Both verifications set, full document set, is_approved = true.
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('Group 1: seeding 20 approved doctors…');

        for ($i = 1; $i <= 20; $i++) {
            [$fname, $lname, $gender, $dob] = $meta($i, 47);

            $user = $makeUser($i, $fname, $lname, $gender, $dob, 'Cairo, Egypt', true);

            Doctor::create([
                'user_id'              => $user->id,
                'specialization'       => $specs[($i - 1) % $specCount],
                'medical_license'      => $docPath('license', $i),
                'degree_certificate'   => $docPath('degree', $i),
                'professional_id_card' => $docPath('id_card', $i),
                'is_approved'          => true,
                'approved_at'          => $now,
                'approved_by'          => $adminId,
                'rejection_reason'     => null,
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // GROUP 2: 10 Pending Doctors  (i = 21 – 30)
        // Email verified, phone NOT verified. One document is missing per row
        // (rotates: no licence → no degree → no ID card).
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('Group 2: seeding 10 pending doctors…');

        for ($i = 21; $i <= 30; $i++) {
            [$fname, $lname, $gender, $dob] = $meta($i, 53);

            $missingDoc = ($i - 21) % 3; // 0=licence, 1=degree, 2=id card

            $user = $makeUser($i, $fname, $lname, $gender, $dob, null, false);

            Doctor::create([
                'user_id'              => $user->id,
                'specialization'       => $specs[($i - 1) % $specCount],
                'medical_license'      => $missingDoc === 0 ? null : $docPath('license', $i),
                'degree_certificate'   => $missingDoc === 1 ? null : $docPath('degree', $i),
                'professional_id_card' => $missingDoc === 2 ? null : $docPath('id_card', $i),
                'is_approved'          => false,
                'approved_at'          => null,
                'approved_by'          => null,
                'rejection_reason'     => null,
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // GROUP 3: 5 Rejected Doctors  (i = 31 – 35)
        // Both verifications set, full docs, is_approved = false,
        // rejection_reason contains a realistic explanation.
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('Group 3: seeding 5 rejected doctors…');

        for ($i = 31; $i <= 35; $i++) {
            [$fname, $lname, $gender, $dob] = $meta($i, 61);

            $user = $makeUser($i, $fname, $lname, $gender, $dob, 'Cairo, Egypt', true);

            Doctor::create([
                'user_id'              => $user->id,
                'specialization'       => $specs[($i - 1) % $specCount],
                'medical_license'      => $docPath('license', $i),
                'degree_certificate'   => $docPath('degree', $i),
                'professional_id_card' => $docPath('id_card', $i),
                'is_approved'          => false,
                'approved_at'          => null,
                'approved_by'          => null,
                'rejection_reason'     => $rejectionReasons[$i - 31],
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // GROUP 4: 5 Soft-Deleted Doctors  (i = 36 – 40)
        // Created and approved, then both Doctor and User are soft-deleted to
        // simulate deactivated / removed accounts.
        // Retrieve with: Doctor::withTrashed()->whereNotNull('deleted_at')
        // ─────────────────────────────────────────────────────────────────────
        $this->command->info('Group 4: seeding 5 soft-deleted doctors…');

        for ($i = 36; $i <= 40; $i++) {
            [$fname, $lname, $gender, $dob] = $meta($i, 71);

            $user = $makeUser($i, $fname, $lname, $gender, $dob, 'Cairo, Egypt', true);

            $doctor = Doctor::create([
                'user_id'              => $user->id,
                'specialization'       => $specs[($i - 1) % $specCount],
                'medical_license'      => $docPath('license', $i),
                'degree_certificate'   => $docPath('degree', $i),
                'professional_id_card' => $docPath('id_card', $i),
                'is_approved'          => true,
                'approved_at'          => $now->copy()->subMonth(),
                'approved_by'          => $adminId,
                'rejection_reason'     => null,
            ]);

            // Soft-delete doctor first (FK constraint), then the user.
            $doctor->delete();
            $user->delete();
        }

        $this->command->info(
            'DoctorSeeder completed — ' .
            '20 approved | 10 pending | 5 rejected | 5 soft-deleted.'
        );
    }
}
