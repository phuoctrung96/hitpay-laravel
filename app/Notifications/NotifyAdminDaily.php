<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;

class NotifyAdminDaily extends Notification
{
    use Queueable;

    public $period;

    public $businesses;

    public $charges;

    public $fastPayouts;

    public $refunds;

    public $commissions;

    public $datetime;

    /**
     * Create a new notification instance.
     *
     * @param string|null $businesses
     * @param string|null $charges
     * @param string|null $fastPayouts
     * @param string|null $refunds
     * @param string|null $commissions
     */
    public function __construct(
        string $period, string $businesses = null, string $charges = null, string $fastPayouts = null,
        string $refunds = null, string $commissions = null
    ) {
        $this->period = $period;
        $this->businesses = $businesses;
        $this->charges = $charges;
        $this->fastPayouts = $fastPayouts;
        $this->refunds = $refunds;
        $this->commissions = $commissions;
        $this->datetime = Date::now();
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
            'mail',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $files = [];

        if (!empty($this->businesses)) {
            $files['businesses'] = $this->businesses;
        }

        if (!empty($this->charges)) {
            $files['charges'] = $this->charges;
        }

        if (!empty($this->fastPayouts)) {
            $files['fast payouts'] = $this->fastPayouts;
        }

        if (!empty($this->refunds)) {
            $files['refunds'] = $this->refunds;
        }

        if (!empty($this->commissions)) {
            $files['commissions'] = $this->commissions;
        }

        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        if (count($files)) {
            $filesExported = implode(', ', array_keys($files));
            $title = "Exported {$filesExported} for Admin";
            $content = "Attached is the exported csv of {$filesExported} ({$this->period}) generated at {$this->datetime->toDateTimeLocalString()}.";
        } else {
            $title = 'No exported file generated';
            $content = 'There were no file or the files are empty when this email is triggered. Please check with support.';
        }

        $message = new MailMessage;

        $message->subject($prefix.$title);
        $message->view('hitpay-email.admin.exports', compact('title', 'content', 'files'));

        return $message;
    }
}
