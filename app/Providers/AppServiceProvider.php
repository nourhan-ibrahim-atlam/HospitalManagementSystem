<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use App\Models\Doctor;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Custom email verification for doctors
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            // Check if notifiable is a Doctor model
            $name = $notifiable instanceof Doctor
                ? $notifiable->user->fname . ' ' . $notifiable->user->lname
                : ($notifiable->name ?? 'User');

            $role = $notifiable instanceof Doctor ? 'Doctor' : 'User';

            return (new MailMessage)
                ->subject('Verify Your Email Address - ' . config('app.name'))
                ->greeting('Hello ' . $name . '!')
                ->line('Thank you for registering as a ' . $role . ' with our platform.')
                ->line('Please click the button below to verify your email address.')
                ->action('Verify Email Address', $url)
                ->line('This verification link will expire in 60 minutes.')
                ->when($notifiable instanceof Doctor, function ($message) {
                    return $message->line('After email verification, an administrator will review and approve your account.')
                                   ->line('You will receive another email once your account is approved.');
                })
                ->line('If you did not create an account, no further action is required.')
                ->salutation('Regards, ' . config('app.name') . ' Team');
        });
    }
}
