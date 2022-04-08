<?php


namespace App\Services\Rates;


use App\Business\PaymentProvider;
use App\Business\PaymentProviderRate;
use App\Enumerations\Business\Channel;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\Business\PaymentMethodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomRatesService
{
    public function setCustomRate(PaymentProvider $paymentProvider, string $method, string $channel, Request $request): void
    {
        $rules = $this->getValidationRules($paymentProvider, $method, $channel);

        $data = \Validator::make($request->all(), $rules)->validate();

        $this->updateRates($paymentProvider, $data['method'], $data['channel'], $data['percentage'], $data['fixed_amount']);
    }

    public function updateRates(PaymentProvider $paymentProvider, string $method, string $channel, $percentage, $fixedAmount ): void
    {
        $paymentProviderRate = new PaymentProviderRate;
        $paymentProviderRate->channel = $channel;
        $paymentProviderRate->method = $method;

        if ($paymentProviderRate->method === 'others') {
            $paymentProviderRate->scenario = $paymentProviderRate->method;
        }

        $paymentProviderRate->percentage = bcdiv($percentage, 100, 4);
        $paymentProviderRate->fixed_amount = bcmul($fixedAmount, 100);

        $paymentProvider->rates()->save($paymentProviderRate);
    }

    private function getValidationRules(PaymentProvider $paymentProvider, string $method, string $channel): array
    {
        $channels = Channel::listConstants();

        $rule = [];

        if (in_array($paymentProvider->payment_provider, [
            PaymentProviderEnum::STRIPE_MALAYSIA,
            PaymentProviderEnum::STRIPE_SINGAPORE,
        ])) {
            $paymentProvider->rates->each(function (PaymentProviderRate $rate) use ($method, $channel) {
                if ($method === $rate->method && $channel === $rate->channel) {
                    throw ValidationException::withMessages([
                        'channel' => 'This selected channel is already assigned with '.str_replace('_', ' ',
                                $rate->method).'.',
                        'method' => 'This selected method is already assigned with '.str_replace('_', ' ',
                                $rate->channel).'.',
                    ]);
                }
            });

            $rule = [
                'method' => [
                    'required',
                    Rule::in([
                      PaymentMethodType::CARD,
                      PaymentMethodType::CARD_PRESENT,
                      PaymentMethodType::WECHAT,
                      PaymentMethodType::ALIPAY,
                      PaymentMethodType::GRABPAY,
                      'others',
                    ]),
                ],
            ];
        } elseif ($paymentProvider->payment_provider === PaymentProviderEnum::GRABPAY) {
          $paymentProvider->rates->each(function (PaymentProviderRate $rate) use (&$channels) {
            if (($key = array_search($rate->channel, $channels)) !== false) {
                unset($channels[$key]);
            }
          });

          $rule = [
            'method' => [
                'required',
                Rule::in([
                  PaymentMethodType::GRABPAY_DIRECT,
                  PaymentMethodType::GRABPAY_PAYLATER,
                ]),
            ],
          ];

        } elseif ($paymentProvider->payment_provider === PaymentProviderEnum::DBS_SINGAPORE) {
            $paymentProvider->rates->each(function (PaymentProviderRate $rate) use (&$channels) {
                if (($key = array_search($rate->channel, $channels)) !== false) {
                    unset($channels[$key]);
                }
            });
            
            $rule = [
                'method' => [
                    'required',
                    Rule::in([
                      PaymentMethodType::PAYNOW,
                      'direct_debit',
                    ]),
                ],
            ];
        }

        return array_merge($rule, [
            'channel' => [
                'required',
                Rule::in($channels),
            ],
            'fixed_amount' => [
                'required',
                'decimal:0,2',
            ],
            'percentage' => [
                'required',
                'numeric',
                'decimal:0,2',
                'max:100',
            ],
        ]);
    }
}
