<?php

namespace App\Notifications\User;

use Illuminate\Auth\Notifications\VerifyEmail as Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->onQueue('emails');
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param \App\User $notifiable
     *
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        return URL::signedRoute('verification.verify', [
            'id' => $notifiable->getKey(),
            'email' => $notifiable->getEmailForPasswordReset(),
        ], Date::now()->addDay()->endOfHour());
    }
}
