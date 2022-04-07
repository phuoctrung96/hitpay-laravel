<?php

namespace App\Notifications\Business\Stripe;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyVerifyAccount extends Notification
{
    use Queueable;

    public $business;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Business $business)
    {
        $this->business = $business;
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
        $title .= 'HitPay - Verify your Stripe Account';

        $link = route('dashboard.business.payment-provider.stripe.home', [
            'business_id' => $this->business->getKey()
        ]);

        return (new MailMessage)->view('hitpay-email.stripe.notify-verify-account', [
            'title' => $title,
            'link' => $link,
            'business' => $this->business,
        ])->subject($title);
    }
}
