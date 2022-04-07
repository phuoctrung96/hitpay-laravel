<?php

namespace App\Business;

use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider;
use HitPay\Agent\LogHelpers;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Data\Objects\PaymentMethods\Card;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentIntent extends Model implements OwnableContract
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * @inheritdoc
     */
    protected $table = 'business_payment_intents';

    /**
     * @inheritdoc
     */
    protected $casts = [
        'data' => 'array',
        'amount' => 'int',
        'expires_at' => 'datetime',
    ];

    /**
     * @inheritdoc
     */
    protected $guarded = [
        //
    ];

    /**
     * Get the charge of the payment intent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charge() : BelongsTo
    {
        return $this->belongsTo(Charge::class, 'business_charge_id', 'id', 'charge');
    }

    /**
     * Get the card of the payment intent, if it has.
     *
     * @return \HitPay\Data\Objects\PaymentMethods\Card|null
     */
    public function card() : ?Card
    {
        if ($this->viaStripe()) {
            if ($this->payment_provider_method === PaymentMethodType::CARD) {
                $card = $this->data['stripe']['payment_method'][$this->payment_provider_method];
            } elseif ($this->payment_provider_method === PaymentMethodType::CARD_PRESENT) {
                $isCardPresented = true;

                $charges = $this->data['stripe']['payment_intent']['charges']['data'];

                $card = $charges[0]['payment_method_details'][$this->payment_provider_method];
            }

            if (isset($card)) {
                return new Card(
                    $isCardPresented ?? false,
                    $card['network'] ?? null,
                    $card['brand'],
                    $card['cardholder_name'] ?? null,
                    $card['country'],
                    $card['exp_month'],
                    $card['exp_year'],
                    $card['funding'],
                    $card['issuer'] ?? null,
                    $card['last4'],
                    $card['read_method'] ?? null
                );
            }
        }

        return null;
    }

    public function viaStripe() : bool
    {
        return in_array($this->payment_provider, [
            PaymentProvider::STRIPE_MALAYSIA,
            PaymentProvider::STRIPE_SINGAPORE,
        ]);
    }
}
