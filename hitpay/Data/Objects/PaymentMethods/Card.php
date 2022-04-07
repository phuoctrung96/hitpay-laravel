<?php

namespace HitPay\Data\Objects\PaymentMethods;

use HitPay\Data\Objects\Base;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class Card extends Base
{
    public bool $presented;

    public ?Carbon $expireAt = null;

    /**
     * Card Constructor
     */
    public function __construct(
        bool $presented,
        ?string $network,
        ?string $brand,
        ?string $holderName,
        ?string $country,
        ?int $expMonth,
        ?int $expYear,
        ?string $funding,
        ?string $issuer,
        ?string $last4,
        ?string $readMethod
    ) {
        $this->presented = $presented;

        $this->data['network'] = strtolower($network);
        $this->data['brand'] = strtolower($brand);

        $brand = Str::snake($brand);
        $brand = preg_replace('/([_\-])/', ' ', $brand);
        $brand = preg_replace('/\s+/', ' ', $brand);

        $this->data['brand_name'] = ucwords($brand);

        $this->data['holder_name'] = $holderName;
        $this->data['country'] = strtolower($country);
        $this->data['country_name'] = get_country_name($this->data['country']) ?? $country;
        $this->data['exp_month'] = $expMonth;
        $this->data['exp_year'] = $expYear;
        $this->data['funding'] = strtolower($funding);
        $this->data['issuer'] = $issuer;
        $this->data['last_4'] = $last4;
        $this->data['read_method'] = $readMethod;

        // TODO - KIV
        //   ---------->>>
        //   -
        //   This should include the timezone of the card.
        //
        if (is_int($expYear)) {
            if (is_int($expMonth)) {
                $this->expireAt = Date::createFromFormat('Y-m', "{$expYear}-{$expMonth}")->endOfMonth();
            } else {
                $this->expireAt = Date::createFromFormat('Y', $expYear)->endOfYear();
            }
        }
    }

    /**
     * Indicate if the card was presented.
     *
     * @return bool
     */
    public function presented() : bool
    {
        return $this->presented;
    }

    /**
     * Get the card expire at.
     *
     * @return \Illuminate\Support\Carbon|null
     */
    public function expireAt() : ?Carbon
    {
        return $this->expireAt;
    }
}
