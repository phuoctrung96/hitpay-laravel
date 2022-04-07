<?php

namespace App\Notifications;

use App\Business;
use App\Enumerations\Business\NotificationChannel;
use HitPay\Firebase\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class Notification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param \App\User $notifiable
     *
     * @return array
     */
    public function getVia($notifiable, string $event)
    {
        if ($notifiable instanceof Business) {
            $channels = $notifiable->subscribedEvents()->where('event', $event)->get()->pluck('channel')->toArray();
        } else {
            $channels = [];
        }

        if (in_array(NotificationChannel::EMAIL, $channels)) {
            $via[] = 'mail';
        }

        if (in_array(NotificationChannel::PUSH_NOTIFICATION, $channels)) {
            $via[] = Channel::class;
        }

        return $via ?? [];
    }

    abstract public function toMail($notifiable);

    abstract public function toFirebase($notifiable);
}
