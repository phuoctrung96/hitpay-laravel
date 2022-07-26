<?php

namespace App\Actions\Business\GatewayProviders;

use App\Business;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider;

class RetrieveShopGatewayProvider extends Action
{
    /**
     * @return array
     * @throws \Exception
     */
    public function process(): array
    {
        if (!$this->business instanceof Business) {
            throw new \Exception("Business not yet set");
        }

        if ($this->business->gatewayProviders()->count() === 0) {
            return [];
        }

        $storeGatewayProvider = $this->business->gatewayProviders()->where('name', 'store')->first();

        if (!$storeGatewayProvider instanceof Business\GatewayProvider) {
            return [];
        }

        $methods = [];

        foreach ($storeGatewayProvider->methods as $method) {
            if ($method === 'payment_online') {
                $methods = array_merge($methods, ['paynow']);
                continue;
            }

            if ($method === 'card') {
                if ($this->business->country === CountryCode::SINGAPORE) {
                    if (
                        $this->business->payment_provider === PaymentProvider::STRIPE_SINGAPORE &&
                        $this->business->usingStripeCustomAccount()
                    ) {
                        $methods = array_merge($methods, ['visa', 'master', 'amex', 'apple', 'unionpay', 'googlepay']);
                    } else {
                        $methods = array_merge($methods, ['visa', 'master', 'amex', 'wechat', 'apple', 'unionpay', 'googlepay']);
                    }
                    continue;
                }

                if ($this->business->country === CountryCode::MALAYSIA) {
                    $methods = array_merge($methods, ['visa', 'master', 'googlepay', 'apple', 'unionpay']);
                    continue;
                }

                if (
                    $this->business->payment_provider === PaymentProvider::STRIPE_US &&
                    $this->business->usingStripeCustomAccount()
                ) {
                    $methods = array_merge($methods, ['visa', 'master', 'amex', 'apple', 'unionpay', 'googlepay']);
                    continue;
                }
            }

            $methods = array_merge($methods, [$method]);
        }

        $result = array_unique($methods);

        return $result;
    }
}
