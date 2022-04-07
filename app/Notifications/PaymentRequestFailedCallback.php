<?php

namespace App\Notifications;

use App\Business\Charge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class PaymentRequestFailedCallback extends Notification
{
    use Queueable;

    private $charge;

    private $params;

    private $callbackUrl;

    private $result;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        Charge $charge, 
        array $params, 
        $callbackUrl, 
        $result
    ) {
        $this->charge       = $charge;
        $this->params       = $params;
        $this->callbackUrl  = $callbackUrl;
        $this->result       = $result;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
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
        $business = $this->charge->business()->first();

        return (new SlackMessage)
            ->error()
            ->content(
                'Business Name: ' . $business->name . "\n" .
                'Charge Key: ' . $this->charge->getKey() . "\n" .
                'Payment Request Key: ' . $this->params['reference_number'] . "\n" .
                'Endpoint: ' . $this->callbackUrl . "\n" .
                'Params: ' . json_encode($this->params) . "\n" .
                'Result: ' . $this->result . "\n" 
            )
        ;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $business = $this->charge->business()->first();

        return [
            'business_name'         => $business->name,
            'payment_key'           => $this->charge->getKey(),
            'payment_request_key'   => $this->params['reference_number'],
            'endpoint'              => $this->callbackUrl,
            'params'                => json_encode($this->params),
            'result'                => $this->result            
        ];
    }
}