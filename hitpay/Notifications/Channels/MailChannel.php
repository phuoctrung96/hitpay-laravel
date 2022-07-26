<?php

namespace HitPay\Notifications\Channels;

use App\FailedEmail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Channels\MailChannel as Channel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailChannel extends Channel
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toMail($notifiable);

        if (!$notifiable->routeNotificationFor('mail', $notification) && !$message instanceof Mailable) {
            return;
        }

        $email = $notifiable->routeNotificationFor('mail', $notification);

        if (is_string($email)) {
            $failedEmail = FailedEmail::query()->find($email);

            if ($failedEmail instanceof FailedEmail && $failedEmail->count >= 3) {
                return;
            }
        }

        if ($message instanceof Mailable) {
            $message->send($this->mailer);
        } elseif ($message instanceof MailMessage) {
            $this->mailer->send(
                $this->buildView($message),
                array_merge($message->data(), $this->additionalMessageData($notification)),
                $this->messageBuilder($notifiable, $notification, $message)
            );
        }

        $failures = $this->mailer->failures();

        foreach ($failures as $email) {
            $failedEmail = FailedEmail::query()->findOrNew($email);

            $failedEmail->email = $email;
            $failedEmail->count = is_int($failedEmail->count) ? $failedEmail->count + 1 : 0;

            $failedEmail->save();
        }
    }
}
