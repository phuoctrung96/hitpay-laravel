<?php

namespace App\Notifications;

use HitPay\Firebase\Channel;
use HitPay\Firebase\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Notifications\Notification;

class RemindFinishVerification extends Notification
{

    public function via($notifiable)
    {
        return [
            'mail',
            Channel::class,
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)->view('hitpay-email.verification-reminder')->subject('Complete HitPay Account Verification with Singpass');
    }
}
