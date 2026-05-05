<?php
// app/Notifications/DoctorApproved.php

namespace App\Notifications;

use App\Models\Doctor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DoctorApproved extends Notification 
{
    use Queueable;

    protected Doctor $doctor;
    protected $approvedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Doctor $doctor, $approvedBy)
    {
        $this->doctor = $doctor;
        $this->approvedBy = $approvedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorEmail = $this->doctor->user->email;
        $doctorName = $this->doctor->user->fname . ' ' . $this->doctor->user->lname;
        $adminName = $this->approvedBy->fname . ' ' . $this->approvedBy->lname;

        $loginUrl = config('app.frontend_url', config('app.url')) . '/login';

        Log::info('Preparing doctor approval email', [
            'to' => $doctorEmail,
            'doctor_name' => $doctorName,
            'admin_name' => $adminName
        ]);

        return (new MailMessage)
            ->subject('Your Doctor Account Has Been Approved - ' . config('app.name'))
            ->greeting('Dear Dr. ' . $doctorName . '!')
            ->line('We are pleased to inform you that your doctor account has been approved by our admin team.')
            ->line('**Account Details:**')
            ->line('- Name: ' . $doctorName)
            ->line('- Specialization: ' . $this->doctor->specialization)
            ->line('- Approved By: ' . $adminName)
            ->line('- Approved At: ' . now()->format('F j, Y, g:i a'))
            ->action('Login to Your Account', $loginUrl)
            ->line('You can now log in to your account and start providing medical services.')
            ->line('If you have any questions or need assistance, please contact our support team.')
            ->salutation('Best regards,\n' . config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'doctor_id' => $this->doctor->id,
            'doctor_name' => $this->doctor->user->fname . ' ' . $this->doctor->user->lname,
            'specialization' => $this->doctor->specialization,
            'approved_by' => $this->approvedBy->fname . ' ' . $this->approvedBy->lname,
            'approved_at' => now()->toDateTimeString(),
            'message' => 'Your doctor account has been approved. You can now login to the system.',
            'type' => 'doctor_approval'
        ];
    }
}
