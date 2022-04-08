<?php

namespace App\Actions\Business\Onboard\Paynow;

use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Jobs\SetCustomPricingFromPartner;
use App\Models\Business\BankAccount;
use HitPay\Data\Countries;
use Illuminate\Support\Facades;
use Illuminate\Validation\Rule;
use App\Actions\Business\Settings\BankAccount\Store as BankAccountStore;
use Throwable;

class Store extends Action
{
    /**
     * @return BankAccount|null
     * @throws Throwable
     */
    public function process() : ?BankAccount
    {
        $country = Countries::get($this->business->country);

        $banksData = $country->banks();

        $data = Facades\Validator::validate($this->data, [
            'company_uen' => 'required|string',
            'company_name' => 'required|string',
            'bank_account_name' => 'required|string',
            'bank_swift_code' => [
                'required',
                Rule::in($banksData->pluck('swift_code')),
            ],
            'bank_account_no' => 'required|digits_between:4,32'
        ]);

        $paymentProviders = $country->paymentProviders();

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
            $provider->payment_provider_account_id = $data['bank_swift_code'].'@'.$data['bank_account_no'];

            $provider->data = [
                'company' => [
                    'name' => $data['company_name'],
                    'uen' => $data['company_uen'],
                ],
                'account' => [
                    'name' => $data['bank_account_name'],
                    'swift_code' => $data['bank_swift_code'],
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

            $bankAccount = null;

            if ($this->isEnableBankAccount) {
                $bankAccount = $this->enableBankAccountFromPaymentProvider($provider);
            }
        } else {
            // create bank account for non-SG
            $bankAccount = $this->createBankAccountFromRequestData($data);
        }

        return $bankAccount;
    }

    /**
     * @param PaymentProvider $paymentProvider
     * @return BankAccount
     * @throws Throwable
     */
    private function enableBankAccountFromPaymentProvider(PaymentProvider $paymentProvider) : BankAccount
    {
        $holderTypesAvailable = [
            'business' => 'company',
            'personal' => 'individual',
        ];

        if (in_array($paymentProvider->business->business_type, $holderTypesAvailable)) {
            $holderType = $paymentProvider->business->business_type;
        } else {
            $holderType = $holderTypesAvailable[$paymentProvider->business->business_type] ?? null;
        }

        if (!( $paymentProvider->business instanceof Business )) {
            throw new \Exception("The payment provider '{$paymentProvider->getKey()}' doesn't attached to any business.");
        }

        try {
            [ $bankSwiftCode, $number ] = explode('@', $paymentProvider->payment_provider_account_id);
        } catch (\Exception $exception) {
            $message = "The business '{$paymentProvider->business->getKey()}' (payment provider '{$paymentProvider->getKey()}') got error '{$exception->getMessage()}' when getting bank swift code and account number hence the bank account has to be created manually.";
            Facades\Log::info($message . '. With errors: ' . $exception->getMessage());
            throw new \Exception($message);
        }

        $data = [
            'bank_swift_code' => $bankSwiftCode,
            'branch_code' => $this->data['bank_branch_code'],
            'currency' => $paymentProvider->business->currency,
            'number' => $number,
            'number_confirmation' => $number,
            'holder_name' => $paymentProvider->data['account']['name'] ?? null,
            'holder_type' => $holderType,
            'use_in_hitpay' => true,
            'use_in_stripe' => false,
            'remark' => 'Extracted from payment provider PayNow',
        ];

        try {
            return BankAccountStore::withBusiness($paymentProvider->business)
                ->data($data)
                ->process();
        } catch (\Exception $exception) {
            $message = "The business '{$paymentProvider->business->getKey()}' (payment provider '{$paymentProvider->getKey()}') is having issue when syncing bank account to Stripe. {$exception->getMessage()} The business will have to sync the bank account manually via update.";
            Facades\Log::info($message . '. With errors: ' . $exception->getMessage());
            throw new \Exception($message);
        }
    }

    /**
     * @param array $requestData
     * @return BankAccount
     * @throws Throwable
     */
    private function createBankAccountFromRequestData(array $requestData): BankAccount
    {
        $bankSwiftCode = $requestData['bank_swift_code'];
        $number = $requestData['bank_account_no'];
        $accountName = $requestData['bank_account_name'] ?? null;

        $holderTypesAvailable = [
            'business' => 'company',
            'personal' => 'individual',
        ];

        if (in_array($this->business->business_type, $holderTypesAvailable)) {
            $holderType = $this->business->business_type;
        } else {
            $holderType = $holderTypesAvailable[$this->business->business_type] ?? null;
        }

        $data = [
            'bank_swift_code' => $bankSwiftCode,
            'branch_code' => $this->data['bank_branch_code'],
            'currency' => $this->business->currency,
            'number' => $number,
            'number_confirmation' => $number,
            'holder_name' => $accountName,
            'holder_type' => $holderType,
            'use_in_hitpay' => true,
            'use_in_stripe' => false,
            'remark' => '',
        ];

        try {
            return BankAccountStore::withBusiness($this->business)
                ->data($data)
                ->canIgnoreBranchCodeForCertainCountries()
                ->process();
        } catch (\Exception $exception) {
            $message = "The business '{$this->business->getKey()}' is having issue when syncing bank account to Stripe. {$exception->getMessage()} The business will have to sync the bank account manually via update.";
            Facades\Log::info($message . '. With errors: ' . $exception->getMessage());
            throw new \Exception($message);
        }
    }
}
