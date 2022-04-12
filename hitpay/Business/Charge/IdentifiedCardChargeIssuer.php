<?php

namespace HitPay\Business\Charge;

use App\CardsIssuer;
use App\Enumerations\Business\PaymentMethodType;
use App\Notifications\NotifyAdminAboutNewCharge;
use App\Notifications\NotifyAdminAboutNonIdentifiableChargeSource;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades;

class IdentifiedCardChargeIssuer
{
    protected \App\Business\Charge $businessCharge;

    /**
     * @throws \Exception
     */
    public function __construct(\App\Business\Charge $businessCharge)
    {
        $this->businessCharge = $businessCharge;
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
        if (!in_array($this->businessCharge->payment_provider_charge_method,
            [PaymentMethodType::CARD, PaymentMethodType::CARD_PRESENT])) {
            throw new \Exception("Check identified charge invalid {type}
                params from business charge id " . $this->businessCharge->getKey());
        }

        $card = $this->businessCharge->card();

        $issuerName = "";

        if ($card instanceof \HitPay\Data\Objects\PaymentMethods\Card) {
            $card = $card->toArray();
            $issuerName = $card['issuer'];
        }

        if ($issuerName === "" || is_null($issuerName)) {
            throw new \Exception("Issuer Name empty from business charge id " . $this->businessCharge->getKey());
        }

        Facades\Notification::route('slack', config('services.slack.new_charges'))
            ->notify(new NotifyAdminAboutNewCharge($this->businessCharge));

        $cardsIssuerWhitelisted = CardsIssuer::where('name', trim($issuerName))->first();

        if (! $cardsIssuerWhitelisted instanceof CardsIssuer) {
            $notifyHandler = new NotifyAdminAboutNonIdentifiableChargeSource($this->businessCharge);
            $notifyHandler->setIssuerName($issuerName);

            Notification::route('slack', config('services.slack.non_identifiable_charge'))
                ->notify($notifyHandler);

            return true;
        }

        return false;
    }
}
