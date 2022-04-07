<?php

namespace HitPay\Firebase;

use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Notification;

class Channel
{
    /**
     * @var \HitPay\Firebase\Firebase
     */
    protected $firebase;

    /**
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * Channel constructor.
     *
     * @param \HitPay\Firebase\Firebase $firebase
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function __construct(Firebase $firebase, Dispatcher $events)
    {
        $this->firebase = $firebase;
        $this->events = $events;
    }

    /**
     * @param \Illuminate\Notifications\Notifiable $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return bool|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function send($notifiable, Notification $notification)
    {
        if (!$token = $notifiable->routeNotificationFor('firebase')) {
            return null;
        } elseif (!method_exists($notification, 'toFirebase')) {
            throw new Exception('\'toFirebase\' is not set in notification.');
        }

        if (is_string($message = $notification->toFirebase($notifiable))) {
            $message = new Message('HitPay', $message);
        } elseif (!$message instanceof Message) {
            return null;
        }

        return $this->firebase->sendMessage($message, $token);
    }
}
