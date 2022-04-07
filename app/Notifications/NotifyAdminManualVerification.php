<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class NotifyAdminManualVerification extends Notification
{
    use Queueable;

    public $business;

    /**
     * Create a new notification instance.
     */
    public function __construct($business) {
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
        return (new MailMessage)
            ->subject('New business pending verification')
            ->line("You have one new business {$this->business->name} pending verification")
            ->line(new HtmlString("<a href='".route('admin.business.index').'?verification_status=pending'."'>Link to pending verification</a>"));
    }
}
