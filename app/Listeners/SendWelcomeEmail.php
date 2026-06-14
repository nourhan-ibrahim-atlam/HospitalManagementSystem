<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
          $user = $event->user;

        Mail::raw(
            "Welcome {$user->name} to our website!",
            function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Welcome');
            }
        );
    }
}