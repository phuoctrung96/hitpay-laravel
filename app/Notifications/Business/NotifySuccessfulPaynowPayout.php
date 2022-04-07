<?php

namespace App\Notifications\Business;

use App\Business\Transfer;
use App\Enumerations\Business\Event;
use App\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class NotifySuccessfulPaynowPayout extends Notification
{
    use Queueable;

    public $transfer;

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
        if ($notifiable instanceof AnonymousNotifiable) {
            return array_keys($notifiable->routes);
        }

        if (in_array('mail', $this->getVia($notifiable, Event::DAILY_COLLECTION))) {
            return [
                'mail'
            ];
        }

        return [];
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
        $title = (App::environment('production') ? '' : '['.App::environment().'] ').'HitPay Balance Payouts for '
            .$this->transfer->business->name.' ('.$this->transfer->created_at->toDateString().')';

        $mailMessage = new MailMessage;

        $mailMessage->view('hitpay-email.paynow-payouts', [
            'title' => $title,
            'transfer' => $this->transfer,
        ])->subject($title);

        if ($this->transfer->payment_provider_transfer_method === 'wallet_fast') {
            $mailMessage->attachData($this->transfer->generateCsv(), "transfer-{$this->transfer->getKey()}.csv");
        }

        return $mailMessage;
    }

    public function toFirebase($notifiable)
    {
        //
    }
}
