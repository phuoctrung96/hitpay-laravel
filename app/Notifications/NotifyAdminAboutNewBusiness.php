<?php

namespace App\Notifications;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class NotifyAdminAboutNewBusiness extends Notification
{
    use Queueable;

    /**
     * @var \App\Business
     */
    public $business;
    public $waNum;

    /**
     * Create a new notification instance.
     *
     * @param \App\Business $business
     */
    public function __construct(Business $business)
    {
        $this->business = $business;
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
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        $message = 'A new business has been created. Let\'s welcome '.$this->business->name.'.';
        $num = $this->business->phone_number;
        $num = str_replace(' ', '', $num);
        $this->waNum = str_replace('+', '', $num);
        if (strlen($num) < 9){
            $this->waNum = "65".$num;
        }

        return (new SlackMessage)->from(config('app.name').(App::environment('production') ? '' : ' ('.App::environment().')'))
            ->content($message)->attachment(function (SlackAttachment $attachment) {
                $attachment->title($this->business->getName())
                    ->content('ID : '.$this->business->id."\n"
                        .'Name : '.$this->business->name."\n"
                        .'WhatsApp : https://api.whatsapp.com/send?phone='.$this->waNum."\n"
                        .'Email : '.$this->business->email."\n"
                        .'Phone Number : '.$this->business->phone_number);
            });
    }
}
