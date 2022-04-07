<?php

namespace App\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

class LoginNotification extends Notification
{
    use Queueable;

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
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';
        $ip = \Request::ip();
        $now = now();

        return (new MailMessage)
            ->subject($prefix.Lang::get('New login detected'))
            ->line(Lang::get('We noticed a new log-in to your HitPay Account at ' . $now . ' with ip address ' . $ip . ' . Please ignore this email if you recognise this login. If not, please contact HitPay support (support@hit-pay.com)'));
    }

}
