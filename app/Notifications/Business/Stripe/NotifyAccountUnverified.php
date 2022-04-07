<?php

namespace App\Notifications\Business\Stripe;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyAccountUnverified extends Notification
{
    use Queueable;

    public Business $business;

    public string $title;

    public array $messages;

    /**
     * Create a new notification instance.
     *
     * @param  string  $title
     * @param  array  $messages
     */
    public function __construct(Business $business, string $title, array $messages)
    {
        $this->business = $business;
        $this->title = $title;
        $this->messages = $messages;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  \App\Business  $notifiable
     *
     * @return string[]
     */
    public function via(Business $notifiable)
    {
        return [ 'mail' ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  \App\Business  $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     * @throws \Exception
     */
    public function toMail(Business $notifiable)
    {
        $mailMessage = new MailMessage;

        $subject = "HitPay - {$this->title}";

        if (!App::isProduction()) {
            $environment = App::environment();

            $subject = "[ {$environment} ] {$subject}";
        }

        $mailMessage->subject($subject);
        $mailMessage->view('hitpay-email.stripe.account-unverified', [
            'title' => $this->title,
            'messages' => $this->messages,
        ]);

        return $mailMessage;
    }
}
