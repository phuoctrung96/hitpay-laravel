<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;

class NotifyAdminPartnersExport extends Notification
{
    use Queueable;

    private string $period;
    private string $attachment;
    private Carbon $datetime;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $period, string $attachment)
    {
        $this->period = $period;
        $this->attachment = $attachment;
        $this->datetime = Date::now();
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
        $files = [];

        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        $title = "Exported partners for Admin";
        $content = "Attached is the exported csv of partners ({$this->period}) generated at {$this->datetime->toDateTimeLocalString()}.";

        $message = new MailMessage;

        $message->subject($prefix.$title);
        $message->view('hitpay-email.admin.exports', compact('title', 'content', 'files'));
        $message->attach($this->attachment);

        return $message;
    }
}
