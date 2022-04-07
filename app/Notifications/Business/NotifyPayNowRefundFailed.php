<?php

namespace App\Notifications\Business;

use App\Business\Charge;
use App\Business\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyPayNowRefundFailed extends Notification
{
    use Queueable;

    public $charge;

    public $referenceId;

    public $amount;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Charge $charge, string $referenceId, int $amount)
    {
        $this->charge = $charge;
        $this->referenceId = $referenceId;
        $this->amount = $amount;
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
        $title .= 'HitPay - Your Refund Transaction Has Failed';

        return (new MailMessage)->view('hitpay-email.paynow-refund-failed', [
            'title' => $title,
            'charge' => $this->charge,
            'reference_id' => $this->referenceId,
            'amount' => getFormattedAmount($this->charge->currency, $this->amount),
        ])->subject($title);
    }
}
