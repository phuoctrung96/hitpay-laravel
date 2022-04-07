<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;

class SendDownloadLinks extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $links;

    /**
     * @var \Illuminate\Support\Carbon
     */
    public $datetime;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, array $links = [])
    {
        $this->title = ( App::environment('production') ? '' : '['.App::environment().'] ' ).$title;
        $this->message = $message;
        $this->links = $links;
        $this->datetime = Date::now();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function via($notifiable) : array
    {
        return empty($this->links) ? [] : [ 'mail' ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = new MailMessage;

        $message->subject($this->title);

        foreach ($this->links as $link) {
            $files[$link['filename']] = $link['url'];
        }

        $message->view('hitpay-email.admin.exports', [
            'title' => $this->title,
            'content' => $this->message,
            'files' => $files ?? [],
        ]);

        return $message;
    }
}
