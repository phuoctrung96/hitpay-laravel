<?php

namespace App\Notifications\Business\Stripe;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyPayoutFailed extends Notification
{
    use Queueable;

    public $stripeFailedMessage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $message)
    {
        $this->stripeFailedMessage = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $title = (App::environment('production') ? '' : '['.App::environment().'] ');
        $title .= 'HitPay - Your Stripe Payout Has Failed';

        return (new MailMessage)->view('hitpay-email.stripe.payout-failed', [
            'title' => $title,
            'stripeFailedMessage' => $this->stripeFailedMessage,
        ])->subject($title);
    }
}
