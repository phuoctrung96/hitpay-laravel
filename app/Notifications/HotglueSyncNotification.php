<?php


namespace App\Notifications;


use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class HotglueSyncNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $messageText;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($from, $to, $jobid)
    {
        $this->messageText = 'Failed to sync products from ' . $from . ' to ' . $to . ' per jobid: ' . $jobid;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param \App\User $notifiable
     *
     * @return array
     */
    public function via(Business $notifiable)
    {
        return [
            'mail',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param \App\User $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(Business $notifiable)
    {
        $title = 'Inventory Sync Notification';
        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        return (new MailMessage)->view('hitpay-email.hotglue-sync-notification', [
            'title' => $title,
            'messageText' => $this->messageText
        ])->subject($title);
    }
}