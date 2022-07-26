<?php

namespace App\Business;

use App\Business;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\PaymentProviderStatus;
use App\Enumerations\OnboardingStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Mail\OnboardingSuccess;
use Exception;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Data;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Mail;

class PaymentProvider extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_payment_providers';

    protected $casts = [
        'data' => 'array',
        'payment_provider_account_ready' => 'bool'
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    protected static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub

        static::saved(function (self $model) {
          if (($model->payment_provider === PaymentProviderEnum::GRABPAY || $model->payment_provider === PaymentProviderEnum::SHOPEE_PAY || $model->payment_provider === PaymentProviderEnum::ZIP) &&
            $model->isDirty('onboarding_status') &&
            $model->onboarding_status === OnboardingStatus::SUCCESS) {
            Mail::to($model->business->email)->send(new OnboardingSuccess($model));
          }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\PaymentProviderRate|\App\Business\PaymentProviderRate[]
     */
    public function rates() : HasMany
    {
        return $this->hasMany(PaymentProviderRate::class, 'business_payment_provider_id', 'id');
    }

    /**
     * @param string $homeCountry
     * @param string $homeCurrency
     * @param string $chargeCurrency
     * @param string $channel
     * @param string $method
     * @param string|null $cardCountry
     * @param string|null $cardBrand
     * @param int|null $amount
     *
     * @return array
     * @throws \Exception
     */
    public function getRateFor(
        string $chargeCurrency, string $channel = null, string $method = null, string $cardCountry = null,
        string $cardBrand = null, int $amount = null
    ) {
        // TODO - 2022-02-13
        //   ----------------->>>
        //   -
        //   We will have to move this into country specs, with configurable features.
        //

        $eligibleCards = [
            'visa',
            'master',
            'mastercard',
        ];

        $eligibleMethods = [
            // Stripe ones
            PaymentMethodType::ALIPAY,
            PaymentMethodType::WECHAT,
            PaymentMethodType::FPX,
            PaymentMethodType::GRABPAY, // Old GrabPay via Stripe
            // Non-Stripe ones
            PaymentMethodType::PAYNOW,
            PaymentMethodType::DIRECT_DEBIT,
            PaymentMethodType::SHOPEE,
            PaymentMethodType::GRABPAY_DIRECT,
            PaymentMethodType::GRABPAY_PAYLATER,
            PaymentMethodType::ZIP
        ];

        $homeCountry = $this->business->country;
        $homeCurrency = $this->business->currency;

        $chargeCurrency = strtolower($chargeCurrency);
        $cardBrand = strtolower($cardBrand);
        $cardCountry = strtolower($cardCountry);

        // Only if the business is from Singapore and the home currency is SGD can do the charge.
        if ($homeCountry === 'sg') {
            $allRateModels = $this->rates()->where('channel', $channel)->get();

            // Only if the charge is in SGD.
            if ($chargeCurrency === $homeCurrency) {
                // If the charge is using a Visa/Master card and the card is issued in Singapore OR the charge is using
                // Alipay/WeChatPay/PayNowOnline, then only they will have custom rate.
                if ((in_array($cardBrand, $eligibleCards) && $cardCountry === 'sg')
                    || in_array($method, $eligibleMethods)) {
                    $rateModels = $allRateModels->where('method', $method)->whereNull('scenario');

                    foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                        if ($rate->starts_at && !$rate->starts_at->isPast()) {
                            continue;
                        } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                            continue;
                        }

                        return [$rate->fixed_amount, $rate->percentage];
                    }

                    if ($method === PaymentMethodType::CARD) {
                        return [50, 0.028];
                    } elseif ($method === PaymentMethodType::CARD_PRESENT) {
                        return [60, 0.025];
                    } elseif ($method === PaymentMethodType::ALIPAY || $method === PaymentMethodType::WECHAT) {
                        return [35, 0.027];
                    } elseif ($method === PaymentMethodType::GRABPAY) {
                        return [0, 0.038];
                    } elseif ($method === PaymentMethodType::PAYNOW) {
                        if ($amount !== null && $amount < 10000) {
                            $minimumFee = 20;
                            $percentage = 0.009;

                            $fee = (int) bcmul($percentage, $amount);

                            if ($fee < $minimumFee) {
                                return [$minimumFee, 0];
                            }

                            return [0, $percentage];
                        }

                        return [30, 0.0065];
                    } elseif ($method === PaymentMethodType::DIRECT_DEBIT) {
                        return [225, 0.0065];
                    } elseif ($method === PaymentMethodType::SHOPEE) {
                        return [0, 0.03];
                    } elseif ($method === PaymentMethodType::GRABPAY_DIRECT) {
                      return [0, 0.03];
                    } elseif ($method === PaymentMethodType::GRABPAY_PAYLATER) {
                      return [0, 0.055];
                    } elseif ($method === PaymentMethodType::ZIP) {
                      return [0, 0.045];
                    }
                }
            }

            // If not charging in SGD, we give the following rate. Card presented will be slightly higher. The
            // remaining will be the same.

            // These are considered as others
            // ------------------------------
            //
            // SGD + Amex
            // SGD + Non-SG issued Card
            // Non-SGD + Amex
            // Non-SGD + SG issued card
            // Non-SGD + Non-SG issued Card

            $rateModels = $allRateModels->where('method', 'others')->where('scenario', 'others');

            foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                if ($rate->starts_at && !$rate->starts_at->isPast()) {
                    continue;
                } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                    continue;
                }

                return [$rate->fixed_amount, $rate->percentage];
            }

            if ($method === 'card_present') {
                return [60, 0.0365];
            }

            return [50, 0.0365];
        } elseif ($homeCountry === 'my') {
            $allRateModels = $this->rates()->where('channel', $channel)->get();

            // Only if the charge is in MYR.
            if ($chargeCurrency === $homeCurrency) {

                if (( in_array($cardBrand, $eligibleCards) && $cardCountry === 'my' )
                    || in_array($method, $eligibleMethods)) {
                    $rateModels = $allRateModels->where('method', $method)->whereNull('scenario');

                    foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                        if ($rate->starts_at && !$rate->starts_at->isPast()) {
                            continue;
                        } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                            continue;
                        }

                        return [ $rate->fixed_amount, $rate->percentage ];
                    }

                    if ($method === PaymentMethodType::CARD) {
                        return [ 100, 0.0225 ];
                    } elseif ($method === PaymentMethodType::ALIPAY) {
                        return [ 100, 0.03 ];
                    } elseif ($method === PaymentMethodType::GRABPAY) {
                        return [ 0, 0.033 ];
                    } elseif ($method === PaymentMethodType::FPX) {
                        return [ 40, 0.02 ];
                    }
                }
            }

            if ($method !== PaymentMethodType::CARD) {
                throw new Exception("Invalid request, non-currency payment for Malaysia Platform only available for method `card`, method `{$method}` requested.");
            }

            $rateModels = $allRateModels->where('method', 'others')->where('scenario', 'others');

            foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                if ($rate->starts_at && !$rate->starts_at->isPast()) {
                    continue;
                } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                    continue;
                }

                return [ $rate->fixed_amount, $rate->percentage ];
            }

            return [ 100, 0.039 ];
        } elseif ($homeCountry === 'us') {
            if (!in_array($method, [
                PaymentMethodType::CARD,
                PaymentMethodType::CARD_PRESENT,
                PaymentMethodType::ALIPAY,
            ])) {
                throw new Exception("Invalid request, we can only make `card` / `card_present` / `alipay` payments for US Platform, method `{$method}` requested.");
            }

            $allRateModels = $this->rates()->where('channel', $channel)->get();

            if ($method === PaymentMethodType::ALIPAY) {
                $rateModels = $allRateModels->where('method', $method)->whereNull('scenario');

                foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                    if ($rate->starts_at && !$rate->starts_at->isPast()) {
                        continue;
                    } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                        continue;
                    }

                    return [ $rate->fixed_amount, $rate->percentage ];
                }

                return [ 40, 0.035 ];
            }

            $rateModels = $allRateModels->where('method', 'others')->where('scenario', 'others');

            foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                if ($rate->starts_at && !$rate->starts_at->isPast()) {
                    continue;
                } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                    continue;
                }

                return [ $rate->fixed_amount, $rate->percentage ];
            }

            return [ 40, 0.024 ];
        } elseif ($homeCountry === 'au') {
            $allRateModels = $this->rates()->where('channel', $channel)->get();

            // Only if the charge is in MYR.
            if ($chargeCurrency === $homeCurrency) {
                $auEligibleCards = $eligibleCards;

                $auEligibleCards[] = 'amex';
                $auEligibleCards[] = 'jcb';
                $auEligibleCards[] = 'unionpay';

                if ((
                        $method === PaymentMethodType::CARD
                        && in_array($cardBrand, $auEligibleCards)
                        && $cardCountry === $homeCountry
                    ) || $method === PaymentMethodType::ALIPAY) {
                    $rateModels = $allRateModels->where('method', $method)->whereNull('scenario');

                    foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                        if ($rate->starts_at && !$rate->starts_at->isPast()) {
                            continue;
                        } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                            continue;
                        }

                        return [ $rate->fixed_amount, $rate->percentage ];
                    }

                    if ($method === PaymentMethodType::CARD) {
                        return [ 50, 0.015 ];
                    } elseif ($method === PaymentMethodType::ALIPAY) {
                        return [ 50, 0.035 ];
                    }
                }
            }

            if ($method !== PaymentMethodType::CARD) {
                throw new Exception("Invalid request, non-currency payment for Malaysia Platform only available for method `card`, method `{$method}` requested.");
            }

            $rateModels = $allRateModels->where('method', 'others')->where('scenario', 'others');

            foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                if ($rate->starts_at && !$rate->starts_at->isPast()) {
                    continue;
                } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                    continue;
                }

                return [ $rate->fixed_amount, $rate->percentage ];
            }

            return [ 50, 0.03 ];
        } elseif ($homeCountry === 'nz') {
            $allRateModels = $this->rates()->where('channel', $channel)->get();

            if ($chargeCurrency === $homeCurrency) {
                if ((
                        $method === PaymentMethodType::CARD
                        && in_array($cardBrand, $eligibleCards)
                        && $cardCountry === $homeCountry
                    ) || $method === PaymentMethodType::ALIPAY) {
                    $rateModels = $allRateModels->where('method', $method)->whereNull('scenario');

                    foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                        if ($rate->starts_at && !$rate->starts_at->isPast()) {
                            continue;
                        } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                            continue;
                        }

                        return [ $rate->fixed_amount, $rate->percentage ];
                    }

                    if ($method === PaymentMethodType::CARD) {
                        return [ 50, 0.024 ];
                    } elseif ($method === PaymentMethodType::ALIPAY) {
                        return [ 50, 0.035 ];
                    }
                }
            }

            if ($method !== PaymentMethodType::CARD) {
                throw new Exception("Invalid request, non-currency payment for Malaysia Platform only available for method `card`, method `{$method}` requested.");
            }

            $rateModels = $allRateModels->where('method', 'others')->where('scenario', 'others');

            foreach ($rateModels->sortByDesc('starts_at') as $rate) {
                if ($rate->starts_at && !$rate->starts_at->isPast()) {
                    continue;
                } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                    continue;
                }

                return [ $rate->fixed_amount, $rate->percentage ];
            }

            return [ 50, 0.03 ];
        }

        throw new Exception('Invalid country, the rate for this country is not set.');
    }

    /**
     * Returns Payment Provider Status
     *
     * @return string
     */
    public function getProviderStatus() : string
    {
        if ($this->onboarding_status === OnboardingStatus::SUCCESS) {
            return PaymentProviderStatus::ENABLED;
        }

        if (
            in_array($this->payment_provider, [
                PaymentProviderEnum::STRIPE_MALAYSIA,
                PaymentProviderEnum::STRIPE_SINGAPORE
            ]) &&
            $this->data['account']['future_requirements']['currently_due'] ?? false
        ) {
            return PaymentProviderStatus::REQUIRES_INFORMATION;
        }

        if (in_array($this->onboarding_status, [
            OnboardingStatus::PENDING_SUBMISSION,
            OnboardingStatus::PENDING_VERIFICATION
        ])) {
            return PaymentProviderStatus::PENDING_APPROVAL;
        }

        throw new Exception('Invalid status');
    }

    /**
     * Returns array of payment methods for current payment provider
     * @return array
     * @throws Exception
     */
    public function getPaymentMethods() : array
    {
        $business_country = $this->business->country;

        $country_data = \HitPay\Data\Countries::get($business_country);
        $payment_providers = $country_data::paymentProviders();

        foreach ($payment_providers as $payment_provider) {
            if ($this->payment_provider === $payment_provider->code) {
                return $payment_provider->methods->toArray();
            }
        }

        return [];
    }

    /**
     * Returns array of payment methods for current payment provider
     * @return array
     * @throws Exception
     */
    public function getPaymentMethodCurrencies(string $selectedMethod) : array
    {
        $business_country = $this->business->country;

        $country_data = \HitPay\Data\Countries::get($business_country);
        $payment_providers = $country_data::paymentProviders();

        foreach ($payment_providers as $payment_provider) {
            if ($this->payment_provider === $payment_provider->code) {
                foreach ($payment_provider->methods as $method) {
                    if ($method->code === $selectedMethod) {
                        $currencies = [];

                        // turn them into a hash map
                        foreach ($method->currencies as $currency) {
                            $currencies[$currency->code] = [
                                'minimum_amount' => $currency->minimum_amount ?? 0
                            ];
                        }

                        return $currencies;
                    }
                }
            }
        }

        return [];
    }

    /**
     * Returns array of payment method codes
     * @return array
     * @throws Exception
     */
    public function getPaymentMethodCodes() : array
    {
        return array_map(fn($m) => $m['code'], $this->getPaymentMethods());
    }

    /**
     * Returns array of integrations for current payment provider
     * @return array
     * @throws Exception
     */
    public function getProviderIntegrations() : array
    {
        $integrations = [];

        $paymentMethods = $this->getPaymentMethodCodes();

        foreach ($this->business->gatewayProviders as $gatewayProvider) {
            if (array_intersect($paymentMethods, $gatewayProvider->methods)) {
                $integrations[] = $gatewayProvider->name;
            }
        }

        return $integrations;
    }

    public function business () : BelongsTo {
      return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function persons() : MorphToMany
    {
        return $this->morphToMany(
            Person::class,
            'associable',
            'business_associable_persons',
            'associable_id',
            'person_id',
            'id',
            'id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function files() : MorphToMany
    {
        return $this->morphToMany(
            File::class,
            'associable',
            'business_associable_file',
            'associable_id',
            'file_id',
            'id',
            'id'
        );
    }

    /**
     * Get the configuration for the payment provider.
     *
     * @return \HitPay\Data\Countries\Objects\PaymentProvider
     * @throws \Exception
     */
    public function getConfiguration() : Data\Countries\Objects\PaymentProvider
    {
        return Data\PaymentProviders::all()->where('code', $this->payment_provider)->first();
    }
}
