<?php

namespace HitPay\Data\Countries\Objects;

use HitPay\Data\Countries\Objects\PaymentProvider\Currency;
use HitPay\Data\Countries\Objects\PaymentProvider\Method;
use Illuminate\Support\Collection;

/**
 * @property-read string $code
 * @property-read string $official_code
 * @property-read string $name
 * @property-read null|Collection|\HitPay\Data\Countries\Objects\PaymentProvider\Currency[] $currencies
 * @property-read null|Collection|\HitPay\Data\Countries\Objects\PaymentProvider\Method[] $methods
 */
class PaymentProvider extends Base
{
    protected function processData(array $data) : array
    {
        $currencies = Collection::make();

        foreach ($data['currencies'] as $_currency) {
            $currencies->push(( new Currency($this->country) )->setData($_currency));
        }

        $this->setChild('currencies', $currencies->unique('code')->sortBy('name')->values());

        $methods = Collection::make();

        foreach ($data['methods'] as $_method) {
            if ($_method['currencies'] === true) {
                $_method['currencies'] = $data['currencies'];
            } else {
                foreach ($_method['currencies'] as $key => $value) {
                    if (is_string($value)) {
                        $value = [ 'code' => $value ];
                    }

                    if (!array_key_exists('minimum_amount', $value)) {
                        $value['minimum_amount'] = 0;
                    }

                    $_method['currencies'][$key] = $value;
                }
            }

            $methods->push(( new Method($this->country) )->setData($_method));
        }

        $this->setChild('methods', $methods->sortBy('code')->values());

        unset(
            $data['methods'],
            $data['currencies'],
        );

        return $data;
    }
}
