<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('notifications')->count() > 0) {
            $this->command->info('NotificationSeeder already run.');
            return;
        }

        $admins   = User::where('role', 'admin')->pluck('id')->toArray();
        $doctors  = User::where('role', 'doctor')->take(20)->pluck('id')->toArray();
        $patients = User::where('role', 'patient')->take(50)->pluck('id')->toArray();

        $notificationTypes = [
            'admin' => [
                [
                    'type'  => 'App\\Notifications\\DoctorPendingApproval',
                    'title' => 'New Doctor Registration',
                    'body'  => 'Dr. {name} has registered and is awaiting approval.',
                ],
                [
                    'type'  => 'App\\Notifications\\PatientRegistered',
                    'title' => 'New Patient Registered',
                    'body'  => 'Patient {name} has been registered in the system.',
                ],
                [
                    'type'  => 'App\\Notifications\\UpdateRequestPending',
                    'title' => 'Update Request Pending',
                    'body'  => 'A data update request requires your review.',
                ],
                [
                    'type'  => 'App\\Notifications\\CriticalEmergency',
                    'title' => 'Critical Emergency Case',
                    'body'  => 'A critical emergency case has been registered. Immediate attention required.',
                ],
            ],
            'doctor' => [
                [
                    'type'  => 'App\\Notifications\\DoctorApproved',
                    'title' => 'Account Approved',
                    'body'  => 'Your account has been approved. You can now access the system.',
                ],
                [
                    'type'  => 'App\\Notifications\\NewPatientAssigned',
                    'title' => 'New Patient Assigned',
                    'body'  => 'A new emergency patient has been assigned to you.',
                ],
                [
                    'type'  => 'App\\Notifications\\UpdateRequestApproved',
                    'title' => 'Update Request Approved',
                    'body'  => 'Your profile update request has been approved.',
                ],
                [
                    'type'  => 'App\\Notifications\\UpdateRequestRejected',
                    'title' => 'Update Request Rejected',
                    'body'  => 'Your profile update request was rejected.',
                ],
            ],
            'patient' => [
                [
                    'type'  => 'App\\Notifications\\WelcomeNotification',
                    'title' => 'Welcome to Elhaqni',
                    'body'  => 'Your account has been created. Your health is our priority.',
                ],
                [
                    'type'  => 'App\\Notifications\\MedicalHistoryAdded',
                    'title' => 'Medical Record Updated',
                    'body'  => 'Your medical record has been updated by your doctor.',
                ],
                [
                    'type'  => 'App\\Notifications\\PrescriptionAdded',
                    'title' => 'New Prescription',
                    'body'  => 'A new prescription has been added to your profile.',
                ],
            ],
        ];

        $now     = Carbon::now();
        $records = [];

        // Admins: 15 notifications each (8 unread, 7 read)
        foreach ($admins as $userId) {
            $pool = $notificationTypes['admin'];
            for ($i = 0; $i < 15; $i++) {
                $notif  = $pool[array_rand($pool)];
                $isRead = $i >= 8; // first 8 unread, last 7 read
                $records[] = $this->buildNotification($userId, $notif, $isRead, $now, '/admin/dashboard');
            }
        }

        // Doctors: 10 notifications each (4 unread, 6 read)
        foreach ($doctors as $userId) {
            $pool = $notificationTypes['doctor'];
            for ($i = 0; $i < 10; $i++) {
                $notif  = $pool[array_rand($pool)];
                $isRead = $i >= 4; // first 4 unread, last 6 read
                $records[] = $this->buildNotification($userId, $notif, $isRead, $now, '/doctor/dashboard');
            }
        }

        // Patients: 5 notifications each (2 unread, 3 read)
        foreach ($patients as $userId) {
            $pool = $notificationTypes['patient'];
            for ($i = 0; $i < 5; $i++) {
                $notif  = $pool[array_rand($pool)];
                $isRead = $i >= 2; // first 2 unread, last 3 read
                $records[] = $this->buildNotification($userId, $notif, $isRead, $now, '/patient/dashboard');
            }
        }

        if (empty($records)) {
            $this->command->warn('No users found to generate notifications for.');
            return;
        }

        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }

        $this->command->info('NotificationSeeder: inserted ' . count($records) . ' notifications.');
    }

    private function buildNotification(
        int|string $userId,
        array $notif,
        bool $isRead,
        Carbon $now,
        string $actionUrl
    ): array {
        return [
            'id'              => Str::uuid()->toString(),
            'type'            => $notif['type'],
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $userId,
            'data'            => json_encode([
                'title'      => $notif['title'],
                'message'    => str_replace('{name}', 'Test User', $notif['body']),
                'action_url' => $actionUrl,
            ]),
            'read_at'    => $isRead ? $now->copy()->subHours(rand(1, 72))->toDateTimeString() : null,
            'created_at' => $now->copy()->subHours(rand(1, 720))->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ];
    }
}
