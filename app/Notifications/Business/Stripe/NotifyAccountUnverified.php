<?php

namespace App\Notifications\Business\Stripe;

use App\Business\PaymentProvider;
use App\Enumerations\CountryCode;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class NotifyAccountUnverified extends Notification
{
    use Queueable;

    protected PaymentProvider $paymentProvider;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(PaymentProvider $paymentProvider)
    {
        $this->paymentProvider = $paymentProvider;
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
        return ['mail'];
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
        $title = (App::environment('production') ? '' : '['.App::environment().'] ');

        $business = $this->paymentProvider->business()->first();

        $paymentMethodName = $business->country === CountryCode::SINGAPORE ? 'Cards and AliPay' : 'HitPay Payment Gateway';

        $title .= "HitPay - Your {$paymentMethodName} Transactions Payouts Are Not Enabled";

        $link = route('dashboard.business.payment-provider.stripe.onboard-verification', [
            'business_id' => $business->getKey()
        ]);

        return (new MailMessage)->view('hitpay-email.stripe.account-unverified', [
            'title' => $title,
            'link' => $link,
            'errorLists' => $this->convertMessageIssue(),
            'stripeData' => $this->paymentProvider->data['account'],
            'paymentMethodName' => $paymentMethodName
        ])->subject($title);
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    private function convertMessageIssue() : Collection
    {
        $stripeData = $this->paymentProvider->data;

        if ($stripeData == null) {
            throw new \Exception("stripe data was empty with business id {$this->paymentProvider->business_id}");
        }

        $stripeData = $stripeData['account'];

        $requirements = $stripeData['requirements'];

        $pastDue = $requirements['past_due'];
        $currentlyDue = $requirements['currently_due'];
        $eventuallyDue = $requirements['eventually_due'];

        $errorList = Collection::make(array_unique(array_merge($pastDue, $currentlyDue, $eventuallyDue)));

        // change name of error list to be
        // make the key array to be word
        $errorList = $errorList->map(function($item) {
            $item = str_replace('.', ' ', $item);
            $item = str_replace('_', ' ', $item);
            return ucfirst($item);
        });

        return $errorList;
    }
}
