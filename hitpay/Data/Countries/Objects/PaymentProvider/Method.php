<?php

namespace HitPay\Data\Countries\Objects\PaymentProvider;

use HitPay\Data\Countries\Objects\Base;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * @property-read string $code
 * @property-read string $name
 * @property-read null|Collection|\HitPay\Data\Countries\Objects\PaymentProvider\Method\Currency[] $currencies
 */
class Method extends Base
{
    protected function processData(array $data) : array
    {
        $_methodLangCode = "misc.method.{$data['code']}";

        if (Lang::has($_methodLangCode)) {
            $data['name'] = Lang::get($_methodLangCode);
        } else {
            $data['name'] = str_replace([ '-', '_' ], ' ', $data['code']);
            $data['name'] = ucwords($data['name']);
        }

        $currencies = Collection::make();

        foreach ($data['currencies'] as $_currency) {
            $currencies->push(( new Method\Currency($this->country) )->setData($_currency));
        }

        $this->setChild('currencies', $currencies->sortBy('name')->values());

        unset($data['currencies']);

        return $data;
    }
}
