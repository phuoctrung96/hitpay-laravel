<?php

namespace App\Notifications\Business\Stripe;

use App\Business\Transfer;
use App\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Stripe\BankAccount;

class NotifySuccessfulWalletPayout extends Notification
{
    use Queueable;

    public Transfer $transfer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
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
     * @throws \Exception
     */
    public function toMail($notifiable)
    {
        $title = (App::environment('production') ? '' : '['.App::environment().'] ').'HitPay Balance Payouts for '
            .$this->transfer->business->name.' ('.$this->transfer->created_at->toDateString().')';

        $mailMessage = new MailMessage;

        $mailMessage->view('hitpay-email.stripe-wallet-payouts', [
            'title' => $title,
            'transfer' => $this->transfer,
        ])->subject($title);

        return $mailMessage;
    }

    public function toFirebase($notifiable)
    {
        //
    }
}
