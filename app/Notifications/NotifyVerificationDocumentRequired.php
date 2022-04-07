<?php

namespace App\Notifications;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyVerificationDocumentRequired extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via(Business $notifiable)
    {
        return [
            'mail',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(Business $notifiable)
    {
        $title = 'Verification Documents Required - Your HitPay Account.';
        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        return (new MailMessage)->view('hitpay-email.verification-document-required', [
            'title' => $title,
        ])->subject($title);
    }
}
