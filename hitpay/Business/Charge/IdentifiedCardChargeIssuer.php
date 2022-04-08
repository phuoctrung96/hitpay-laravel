<?php

namespace HitPay\Business\Charge;

use App\CardsIssuer;
use App\Enumerations\Business\PaymentMethodType;
use App\Notifications\NotifyAdminAboutNewCharge;
use App\Notifications\NotifyAdminAboutNonIdentifiableChargeSource;
use Illuminate\Support\Facades\Notification;
use Stripe\Charge;
use Illuminate\Support\Facades;

class IdentifiedCardChargeIssuer
{
    protected object $charge;
    protected string $type;
    protected \App\Business\Charge $businessCharge;

    /**
     * @throws \Exception
     */
    public function __construct(object $charge, \App\Business\Charge $businessCharge, bool $strict = true)
    {
        if ($strict) {
            if (!$charge instanceof Charge) {
                throw new \Exception("Charge data not from stripe charge object");
            }
        }

        $this->charge = $charge;
        $this->businessCharge = $businessCharge;
        $this->type = $businessCharge->payment_provider_charge_method;
    }

    /**
     * Check Issuer name
     * True : Issuer name NOT in whitelist
     * False : Issuer name in whitelist
     * @return bool
     * @throws \Exception
     */
    public function process() : bool
    {
        if (!in_array($this->type, [PaymentMethodType::CARD, PaymentMethodType::CARD_PRESENT])) {
            throw new \Exception("Check identified charge invalid {type} params from charge id " . $this->charge->id);
        }

        $issuerName = '';

        if ($this->type == PaymentMethodType::CARD) {
            if (isset($this->charge->source->card->issuer)) {
                $issuerName = $this->charge->source->card->issuer;
            } else if (isset($this->charge->payment_method_details->card->issuer)) {
                $issuerName = $this->charge->payment_method_details->card->issuer;
            }
        }

        if ($this->type == PaymentMethodType::CARD_PRESENT) {
            if (isset($this->charge->payment_method_details->card_present->issuer)) {
                $issuerName = $this->charge->payment_method_details->card_present->issuer;
            }
        }

        if ($issuerName == "") {
            throw new \Exception("Issuer Name empty from charge id " . $this->charge->id);
        }

        Facades\Notification::route('slack', config('services.slack.new_charges'))
            ->notify(new NotifyAdminAboutNewCharge($this->businessCharge));

        $cardsIssuerWhitelisted = CardsIssuer::where('name', trim($issuerName))->first();

        if ($cardsIssuerWhitelisted === null) {
            Facades\Log::info("cards issuer " . $issuerName . " null with id " . $this->charge->id);

            $notifyHandler = new NotifyAdminAboutNonIdentifiableChargeSource($this->businessCharge);
            $notifyHandler->setIssuerName($issuerName);

            Notification::route('slack', config('services.slack.non_identifiable_charge'))
                ->notify($notifyHandler);

            return true;
        } else {
            Facades\Log::info("cards issuer " . $issuerName . " set with id " . $this->charge->id);
            return false;
        }
    }
}
