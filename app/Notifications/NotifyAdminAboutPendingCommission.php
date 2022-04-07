<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class NotifyAdminAboutPendingCommission extends Notification
{
    use Queueable;

    public $data = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return [
            'slack',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param $notifiable
     *
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        $message = new SlackMessage;
        $message = $message->from(Config::get('app.name')
            .(App::environment('production') ? '' : ' ('.App::environment().')'));
        $message = $message->content('Pending commission today!');

        foreach ($this->data as $data) {
            $message = $message->attachment(function (SlackAttachment $attachment) use ($data) {
                $attachment->title($data['business_name'])->content('ID : '.$data['id']."\n"
                    .'Business ID : '.$data['business_id']."\n"
                    .'Bank Swift Code : '.($data['bank_swift_code'] ?? null)."\n"
                    .'Bank Account No : '.($data['bank_account_no'] ?? null)."\n"
                    .'Amount : '.$data['amount']);
            });
        }

        return $message;
    }
}
