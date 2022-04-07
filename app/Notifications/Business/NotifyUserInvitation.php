<?php

namespace App\Notifications\Business;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyUserInvitation extends Notification
{
    use Queueable;

    /**
     * @var Business
     */
    private $business;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
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
        $title = (App::environment('production') ? '' : '['.App::environment().'] ');
        $title .= 'HitPay - You are invited to join ' . $this->business->name;
        $url = empty($notifiable->password)
            ? route('register-complete', ['hash' => encrypt($notifiable->id)])
            : route('dashboard.pending-invitations.index');

        return (new MailMessage)->view('hitpay-email.business-invite-user', [
            'title' => $title,
            'business' => $this->business,
            'notifiable' => $notifiable,
            'url' => $url,
        ])
            ->subject($title);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
