<?php

namespace App\Notifications;

use App\Enumerations\Business\Event;
use Carbon\Carbon;
use HitPay\Firebase\Message;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class SummarizeDailyCollection extends Notification
{
    public $date;

    public $currencies;

    public function __construct(Carbon $date, array $currencies)
    {
        $this->date = $date;
        $this->currencies = $currencies;
    }

    public function via($notifiable)
    {
        return $this->getVia($notifiable, Event::DAILY_COLLECTION);
    }

    /**
     * @param $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage|null
     * @throws \ReflectionException
     */
    public function toMail($notifiable)
    {
        foreach ($this->currencies as $code => $amount) {
            $currencies[] = [
                'code' => $code,
                'amount' => getFormattedAmount($code, $amount, false),
            ];
        }

        if (isset($currencies)) {
            return (new MailMessage)->view('hitpay-email.daily-summary', [
                'date' => $this->date->toDateString(),
                'currencies' => $currencies,
            ])->subject('HitPay Daily Collections Report on '.$this->date->toDateString());
        }

        return null;
    }

    /**
     * @param $notifiable
     *
     * @return \HitPay\Firebase\Message
     * @throws \ReflectionException
     */
    public function toFirebase($notifiable)
    {
        foreach ($this->currencies as $code => $amount) {
            $currencies[] = getFormattedAmount($code, $amount);
        }

        if (isset($currencies)) {
            $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

            return new Message($prefix.'Your Sales Collections from yesterday',
                'Your total sales yesterday totalled '.implode(', ', $currencies)
                .'. Export your sales reports from the web dashboard or in-app, to get insights on product sales and customer data');
        }

        return null;
    }
}
