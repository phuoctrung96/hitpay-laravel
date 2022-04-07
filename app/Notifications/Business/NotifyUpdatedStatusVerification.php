<?php

namespace App\Notifications\Business;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\HtmlString;

class NotifyUpdatedStatusVerification extends Notification
{
    use Queueable;

    /**
     * @var Business
     */
    private $status;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($status)
    {
        $this->status = $status;
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
        if ($this->status == 'Rejected') {
            $title = (App::environment('production') ? '' : '[' . App::environment() . '] ');
            $title .= 'Your HitPay account verification has been rejected';

            return (new MailMessage)->view('hitpay-email.business-verification-rejected', [
                'title' => $title,
            ])->subject($title);
        } else {
            $title = (App::environment('production') ? '' : '[' . App::environment() . '] ');
            $title .= 'Your HitPay account verification has been approved';

            return (new MailMessage)->view('hitpay-email.business-verification-approved', [
                'title' => $title,
            ])->subject($title);
        }

        return (new MailMessage)->subject('Your verification was '.$this->status);
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
