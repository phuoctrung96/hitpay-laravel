<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendFile extends Notification
{
    public $title;

    public $messages;

    public $filename;

    public $content;

    public $type;

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param array $messages
     * @param string $filename
     * @param string $content
     * @param string $type
     */
    public function __construct(string $title, array $messages, string $filename, string $content, $type = 'csv')
    {
        $this->title = $title;
        $this->messages = $messages;
        $this->filename = $filename;
        $this->content = $content;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return [
            'mail',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)->view('hitpay-email.download')->subject($this->title);

        foreach ($this->messages as $content) {
            $message->line($content);
        }

        if ($this->type == 'csv') {
            return $message->attachData($this->content, $this->filename . '.csv', [
                'mime' => 'text/csv',
            ]);
        }
        elseif ($this->type == 'pdf'){
            return $message->attachData($this->content, $this->filename . '.pdf');
        }
    }
}
