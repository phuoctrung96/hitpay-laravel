<?php

namespace HitPay\Data\Countries\Objects\PaymentProvider\Method;

use App\Enumerations\CurrencyCode;
use HitPay\Data\Countries\Objects\Base;
use Illuminate\Support\Facades\Lang;

/**
 * @property-read string $code
 * @property-read string $name
 * @property-read int|null $minimum_amount
 * @property-read bool $zero_decimal
 */
class Currency extends Base
{
    protected function processData(array $data) : array
    {
        $_currencyLangCode = "misc.currency.{$data['code']}";

        if (Lang::has($_currencyLangCode)) {
            $data['name'] = Lang::get($_currencyLangCode);
        } else {
            $data['name'] = strtoupper($data['code']);
        }

        $data['zero_decimal'] = CurrencyCode::isZeroDecimal($data['code']);

        return $data;
    }
}
