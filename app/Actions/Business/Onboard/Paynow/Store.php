<?php

namespace App\Actions\Business\Onboard\Paynow;

use App\Actions\Business\Action;
use App\Actions\Business\Settings\BankAccount\Store as BankAccountStore;
use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Jobs\SetCustomPricingFromPartner;
use App\Models\Business\BankAccount;
use Exception;
use Illuminate\Support\Facades;
use Illuminate\Validation\Rule;
use Stripe\Exception\InvalidRequestException;
use Throwable;

class Store extends Action
{
    /**
     * @return BankAccount|null
     * @throws Throwable
     */
    public function process() : ?BankAccount
    {
        $banksData = $this->business->banksAvailable();

        $shouldUseBanksList = in_array($this->business->country, [CountryCode::SINGAPORE, CountryCode::MALAYSIA]);

        $rules = [
            'company_uen' => 'required|string',
            'company_name' => 'required|string',
            'bank_account_name' => 'required|string|max:160',
        ];

        if ($shouldUseBanksList) {
            $rules['bank_id'] = [ 'required', Rule::in($banksData->pluck('id')) ];
        } else {
            /**
             * Countries that use Routing Number
             */
            if (in_array($this->business->country, BankAccountStore::ROUTING_NUMBER_COUNTRIES)) {
                $rules['bank_routing_number'] = 'required|string|max:15';
            }

            /**
             * Countries that use SWIFT code
             */
            if (in_array($this->business->country, BankAccountStore::SWIFT_CODE_COUNTRIES)) {
                $rules['bank_swift_code'] = 'required|string|min:8|max:11';
            }
        }

        if (in_array($this->business->country, BankAccountStore::IBAN_COUNTRIES)) {
            $rules['bank_account_no'] = 'required|regex:/(^[A-Z]{2}\w{4,32}$)/u';
        } else {
            $rules['bank_account_no'] = 'required|digits_between:4,32';
        }

        $data = Facades\Validator::validate($this->data, $rules);

        if ($shouldUseBanksList) {
            /** @var \HitPay\Data\Countries\Objects\Bank $bank */
            $bank = $banksData->where('id', $data['bank_id'])->first();
        }

        $paymentProviders = $this->business->paymentProvidersAvailable();

        if (in_array(PaymentProviderEnum::DBS_SINGAPORE, $paymentProviders->pluck('data.code')->toArray())) {
            $providers = $this->business->paymentProviders()
                ->whereNotNull('payment_provider_account_id')
                ->get();

            /** @var \App\Business\PaymentProvider $provider */
            $provider = $providers->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)
                ->first();

            $isExisting = $provider instanceof PaymentProvider;

            if (!$isExisting) {
                $provider = new PaymentProvider;
            }

            $provider->payment_provider = PaymentProviderEnum::DBS_SINGAPORE;
            $provider->payment_provider_account_id = $bank->swift_code.'@'.$data['bank_account_no'];

            $provider->data = [
                'company' => [
                    'name' => $data['company_name'],
                    'uen' => $data['company_uen'],
                ],
                'account' => [
                    'name' => $data['bank_account_name'],
                    'swift_code' => $bank->swift_code,
                    'number' => $data['bank_account_no'],
                ],
            ];

            if (!$isExisting) {
                $this->business->paymentProviders()->save($provider);

                if ($this->business->partner) {
                    dispatch(new SetCustomPricingFromPartner($this->business->partner, $provider));
                }
            } else {
                $provider->save();
            }
        }

        $data = [
            'bank_id' => $bank->id ?? null,
            'branch_code' => $this->data['bank_branch_code'] ?? null,
            'currency' => $this->business->currency,
            'number' => $data['bank_account_no'],
            'number_confirmation' => $data['bank_account_no'],
            'bank_routing_number' => $data['bank_routing_number'] ?? null,
            'bank_swift_code' => $data['bank_swift_code'] ?? null,
            'holder_name' => $data['bank_account_name'] ?? null,
            'holder_type' => $this->business->getStripeAccountBusinessType(),
            'use_in_hitpay' => true,
            'use_in_stripe' => false,
        ];

        return BankAccountStore::withBusiness($this->business)->data($data)->process();
    }
}
