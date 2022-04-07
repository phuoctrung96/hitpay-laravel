<?php

namespace App\Notifications;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class XeroAccountDisconnectedNotification extends Notification
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
        $title .= 'HitPay. Xero account disconnected. Please reauthorise access - ' . $this->business->name;
        $loginUrl = url('login');
        $xeroIntegrationUrl = route('dashboard.business.integration.xero.home', $this->business);

        return (new MailMessage)->view('hitpay-email.xero-disconnected', [
            'title' => $title,
            'business' => $this->business,
            'notifiable' => $notifiable,
            'loginUrl' => $loginUrl,
            'xeroIntegrationUrl' => $xeroIntegrationUrl,
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
