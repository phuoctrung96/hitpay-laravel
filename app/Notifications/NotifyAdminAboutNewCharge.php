<?php

namespace App\Notifications;

use App\Business\Charge;
use App\Enumerations\Business\PaymentMethodType;
use Illuminate\Bus\Queueable;
use App\Enumerations\PaymentProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyAdminAboutNewCharge extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var \App\Business\Charge
     */
    public $charge;

    /**
     * Create a new notification instance.
     *
     * @param \App\Business\Charge $charge
     */
    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
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
        return (new SlackMessage)->from(config('app.name').(App::environment('production') ? '' : ' ('.App::environment().')'))
            ->content('A new charge has been made!')->attachment(function (SlackAttachment $attachment) {
                $businessName = $this->charge->business->getName();
                $amount = getFormattedAmount($this->charge->currency, $this->charge->amount);
                $customerEmail = $this->charge->customer_email ?? '- none -';
                $paymentSource = '';
                if ($this->charge->payment_provider === PaymentProvider::DBS_SINGAPORE && isset($this->charge->data['txnInfo'])) {
                    $paymentSource = $this->charge->data['txnInfo']['senderParty']['senderBankId'];
                }
                elseif($this->charge->payment_provider === PaymentProvider::GRABPAY) {
                    $paymentSource = 'GrabPay Singapore';
                }

                $content = 'ID : '.$this->charge->id."\n"
                    .'Business ID : '.$this->charge->business_id."\n"
                    .'Business Name : '.$businessName."\n"
                    .'Customer Email : '.$customerEmail."\n"
                    .'Channel : '.$this->charge->channel."\n"
                    .'Payment Method : '.$this->charge->payment_provider_charge_method."\n";

                if ($this->charge->payment_provider === PaymentProvider::STRIPE_SINGAPORE) {
                    $issuerName = '';

                    if ($this->charge->payment_provider_charge_method == PaymentMethodType::CARD) {
                        if (isset($this->charge->data['source']['card']['issuer'])) {
                            $issuerName = $this->charge->data['source']['card']['issuer'];
                        } else if (isset($this->charge->data['payment_method_details']['card']['issuer'])) {
                            $issuerName = $this->charge->data['payment_method_details']['card']['issuer'];
                        }
                    }

                    if ($this->charge->payment_provider_charge_method == PaymentMethodType::CARD_PRESENT) {
                        if (isset($this->charge->data['payment_method_details']['card_present']['issuer'])) {
                            $issuerName = $this->charge->data['payment_method_details']['card_present']['issuer'];
                        }
                    }

                    $paymentSource = $issuerName;

                    $content .= 'Issuer Name : '. $paymentSource."\n";
                } else {
                    $content .= 'Payment Source : '. $paymentSource."\n";
                }

                $attachment->title($businessName)
                    ->content($content);
            });
    }
}
