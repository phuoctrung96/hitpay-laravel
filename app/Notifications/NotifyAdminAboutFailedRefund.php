<?php

namespace App\Notifications;

use App\Business\Charge;
use App\Business\Refund;
use App\Business\RefundIntent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyAdminAboutFailedRefund extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var \App\Business\Charge
     */
    public $charge;

    /**
     * @var \App\Business\Refund
     */
    public $refund;

    /**
     * @var \App\Business\RefundIntent
     */
    public $refundIntent;

    /**
     * @var string
     */
    public $reference;

    /**
     * @var string|null
     */
    public $response;

    /**
     * Create a new notification instance.
     *
     * @param \App\Business\Charge $charge
     * @param \App\Business\Refund $refund
     * @param \App\Business\RefundIntent $refundIntent
     * @param string $reference
     * @param string|null $response
     */
    public function __construct(
        Charge $charge, Refund $refund, RefundIntent $refundIntent, string $reference, string $response = null
    ) {
        $this->charge = $charge;
        $this->refund = $refund;
        $this->refundIntent = $refundIntent;
        $this->reference = $reference;
        $this->response = $response;
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
     * @throws \ReflectionException
     */
    public function toSlack($notifiable)
    {
        $name = config('app.name').(App::environment('production') ? '' : ' ('.App::environment().')');

        return (new SlackMessage)->from($name)
            ->content('A refund ('.$this->refundIntent->payment_provider.') is failed.')
            ->attachment(function (SlackAttachment $attachment) {
                $businessName = $this->charge->business->getName();
                $amount = getFormattedAmount($this->charge->currency, $this->refund->amount);
                $customerEmail = $this->charge->customer_email ?? '- none -';

                $attachment->title($businessName)
                    ->content('ID : '.$this->charge->id."\n"
                        .'Business ID : '.$this->charge->business_id."\n"
                        .'Business Name : '.$businessName."\n"
                        .'Customer Email : '.$customerEmail."\n"
                        .'Channel : '.$this->charge->channel."\n"
                        .'Refund Provider : '.$this->refundIntent->payment_provider."\n"
                        .'Refund Reference : '.$this->refundIntent->payment_provider_object_id."\n"
                        .'Amount : '.$amount."\n\n"
                        .$this->response);
            });
    }
}
