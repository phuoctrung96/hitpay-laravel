<?php

namespace App\Notifications;

use App\Business\Charge;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifyAdminAboutNonIdentifiableChargeSource extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var \App\Business\Charge
     */
    public $charge;

    /**
     * @var \App\User|null
     */
    public ?User $admin = null;

    public string $issuerName = '';

    /**
     * Create a new notification instance.
     *
     * @param \App\Business\Charge $charge
     * @param User|null $admin
     */
    public function __construct(Charge $charge, User $admin = null) {
        $this->charge = $charge;
        $this->admin = $admin;
    }

    /**
     * @param string $issuerName
     * @return $this
     */
    public function setIssuerName(string $issuerName) : self
    {
        $this->issuerName = $issuerName;

        return $this;
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
            ->content('Source of charge is non-identifiable!')->attachment(function (SlackAttachment $attachment) {
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

                $adminName = $this->admin == null ? null : $this->admin->display_name;
                $adminEmail = $this->admin == null ? null : $this->admin->email;

                $content = 'ID : '.$this->charge->id."\n"
                    .'Business ID : '.$this->charge->business_id."\n"
                    .'Business Name : '.$businessName."\n"
                    .'Customer Email : '.$customerEmail."\n"
                    .'Channel : '.$this->charge->channel."\n"
                    .'Payment Method : '.$this->charge->payment_provider_charge_method."\n";

                if ($this->charge->payment_provider === PaymentProvider::STRIPE_SINGAPORE) {
                    $paymentSource = $this->issuerName;

                    if ($adminEmail == null or $adminEmail == null) {
                        $content .= 'Issuer Name : '. $paymentSource."\n"
                            .'Amount : '.$amount."\n";
                    } else {
                        $content .= 'Issuer Name : '. $paymentSource."\n"
                            .'Amount : '.$amount."\n"
                            .'Admin Name : '. $adminName."\n"
                            .'Admin Email : '. $adminEmail;
                    }
                } else {
                    $content .= 'Payment Source : '. $paymentSource."\n"
                        .'Amount : '.$amount."\n"
                        .'Admin Name : '. $adminName."\n"
                        .'Admin Email : '. $adminEmail;
                }

                $attachment->title($businessName)
                    ->content($content);
            });
    }
}
