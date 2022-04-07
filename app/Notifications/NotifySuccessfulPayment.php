<?php

namespace App\Notifications;

use App\Business\Charge;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\Channel as BusinessChannel;
use App\Enumerations\Business\Event;
use HitPay\Firebase\Channel as FirebaseChannel;
use HitPay\Firebase\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class NotifySuccessfulPayment extends Notification implements ShouldQueue
{
    use Queueable;

    public $charge;

    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }

    public function via($notifiable)
    {
        $via = $this->getVia($notifiable, Event::INCOMING_PAYMENT);

        return count($via) ? $via : [
            FirebaseChannel::class,
        ];
    }

    public function toFirebase($notifiable)
    {
        $message = App::environment('production') ? '' : '['.App::environment().'] ';
        $message .= 'You received a payment of '.getFormattedAmount($this->charge->currency, $this->charge->amount);

        if ($this->charge->channel === Channel::PAYMENT_GATEWAY) {
            if ($this->charge->plugin_provider_order_id) {
                $message .= ' Order ID: '.$this->charge->plugin_provider_order_id
                    .' (REF: '.$this->charge->plugin_provider_reference.')';
            } else {
                $message .= ' REF: '.$this->charge->plugin_provider_reference;
            }
        } else {
            // POS no push needed as merchant is already on POS
            return;
        }

        return new Message($message);
    }

    /**
     * @param \App\Business $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     * @throws \ReflectionException
     */
    public function toMail($notifiable)
    {
        $title = App::environment('production') ? '' : '['.App::environment().'] ';
        $title .= 'New payment of '.getFormattedAmount($this->charge->currency, $this->charge->amount);

        return (new MailMessage)->view('hitpay-email.successful-payment', [
                'title' => $title,
                'charge' => $this->charge
            ])->subject($title);
    }
}
