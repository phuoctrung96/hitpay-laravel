<?php

namespace App\Notifications;

use App\Business\Commission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class NotifySuccessfulCommissionPayout extends Notification
{
    use Queueable;

    /**
     * @var \App\Business\Commission
     */
    public $commission;

    /**
     * Create a new notification instance.
     */
    public function __construct(Commission $commission)
    {
        $this->commission = $commission;
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
    public function toMail($notifiable)
    {
        $title = 'Commission Payouts for '.$this->commission->business->name
            .' ('.$this->commission->created_at->toDateString().', '.$this->commission->charges->count().' charges)';
        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        return (new MailMessage)->view('hitpay-email.commission-payouts', [
            'title' => $title,
            'commission' => $this->commission,
        ])->subject($title);
    }

    public function toFirebase($notifiable)
    {
        //
    }
}
