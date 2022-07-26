<?php

namespace App\Actions\Business\Settings\BankAccount;

use App\Actions\Exceptions\BadRequest;
use App\Enumerations\Business\Type as BusinessType;
use App\Enumerations\CountryCode;
use App\Models\Business\BankAccount;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use HitPay\Stripe\CustomAccount\ExternalAccount\Create;
use Illuminate\Support\Facades;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\InvalidRequestException;
use Throwable;

class Store extends Action
{
    protected bool $dryRun = false;

    /**
     * Store a new bank account for a business.
     *
     * @return \App\Models\Business\BankAccount
     * @throws \App\Actions\Exceptions\BadRequest
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function process() : BankAccount
    {
        $currencyCodesData = $this->business->currenciesAvailable();
        $banksData = $this->business->banksAvailable();

        $shouldUseBanksList = in_array($this->business->country, [CountryCode::SINGAPORE, CountryCode::MALAYSIA]);

        $rules['currency'] = [ 'required', Rule::in($currencyCodesData) ];

        /**
         * We have list of the banks only for Malaysia and Singapore
         * everyone should enter the data manually
         */
        if ($shouldUseBanksList) {
            $rules['bank_id'] = ['required', Rule::in($banksData->pluck('id'))];
        } else {
            /**
             * Countries that use Routing Number
             */
            if (in_array($this->business->country, self::ROUTING_NUMBER_COUNTRIES)) {
                $rules['bank_routing_number'] = 'required|string|max:15';
            }

            /**
             * Countries that use SWIFT code
             */
            if (in_array($this->business->country, self::SWIFT_CODE_COUNTRIES)) {
                $rules['bank_swift_code'] = 'required|string|min:8|max:11';
            }
        }

        // Not all country requires branch code. E.g. Malaysia, Malaysia is using swift code only.
        //
        // have to make branch code not necessity
        if ($this->business->country === CountryCode::SINGAPORE) {
            if ($this->requireBranchCodeForCertainCountries) {
                $rules['branch_code'][] = 'required_with:bank_id';
            } else {
                $rules['branch_code'][] = 'nullable';
            }

            $_bankId = $data['bank_id'] ?? null;

            if (is_string($_bankId)) {
                /** @var \HitPay\Data\Countries\Objects\Bank $_bank */
                $_bank = $banksData->where('id', $_bankId)->first();

                if ($_bank && $_bank->useBranch) {
                    $rules['branch_code'][] = Rule::in($_bank->branches->pluck('code')->toArray());
                }
            }
        }

        if (in_array($this->business->country, self::IBAN_COUNTRIES)) {
            $rules['number'] = 'required|regex:/(^[A-Z]{2}\w{4,32}$)/u';
        } else {
            $rules['number'] = 'required|digits_between:4,32';
        }

        $rules['holder_name'] = 'required|string|max:160';
        $rules['holder_type'] = [ 'required', Rule::in([ BusinessType::COMPANY, BusinessType::INDIVIDUAL, BusinessType::PARTNER ]) ];
        $rules['use_in_hitpay'] = 'required|bool';
        $rules['use_in_stripe'] = 'required|bool';
        $rules['remark'] = 'nullable|string|max:255';

        $data = Facades\Validator::validate($this->data, $rules);

        if ($shouldUseBanksList) {
            /** @var \HitPay\Data\Countries\Objects\Bank $bank */
            $bank = $banksData->where('id', $data['bank_id'])->first();

            if ($bank->useBranch) {
                if (array_key_exists('branch_code', $data) && !is_null($data['branch_code'])) {
                    $branch = $bank->branches->where('code', $data['branch_code'])->first();

                    $bankRoutingNumber = $branch->routing_number;
                } else {
                    $bankRoutingNumber = null;
                }
            } else {
                $bankRoutingNumber = $bank->swift_code;
            }

            $bankSwiftCode = $bank->swift_code;
        } else {
            $bankSwiftCode = $data['bank_swift_code'] ?? null;
            $bankRoutingNumber = $data['bank_routing_number'] ?? null;
        }

        $bankAccount = new BankAccount;

        // Currently, we allow bank account from the same country of the business only.
        //
        $bankAccount->country = $this->business->country;
        $bankAccount->currency = $data['currency'];
        $bankAccount->bank_swift_code = $bankSwiftCode;
        $bankAccount->bank_routing_number = $bankRoutingNumber;
        $bankAccount->number = $data['number'];

        if ($shouldUseBanksList) {
            $bankData = $bank->toArray();

            unset($bankData['branches']);

            $bankAccount->data = [
                'data' => [
                    'bank' => $bankData,
                ],
            ];
        }

        $bankAccount->holder_name = $data['holder_name'];
        $bankAccount->holder_type = $data['holder_type'];
        $bankAccount->remark = $data['remark'] ?? null;

        // This is returning the "bank account" model, without actual creating it.
        //
        if ($this->dryRun) {
            return $bankAccount;
        }

        $useInHitPay = $data['use_in_hitpay'];

        if (!$useInHitPay) {
            $useInHitPay = $this->business->bankAccounts->where('hitpay_default', true)->count() === 0;
        }

        Facades\DB::beginTransaction();

        try {
            $this->business->bankAccounts()->save($bankAccount);

            if ($useInHitPay) {
                SetAsDefaultForHitPayPayout::withBusiness($this->business)->bankAccount($bankAccount)->process();
            }

            Facades\DB::commit();
        } catch (Throwable $throwable) {
            Facades\DB::rollBack();

            throw $throwable;
        }

        if ($this->business->usingStripeCustomAccount()) {
            // check partner
            if ($this->business->business_type == BusinessType::PARTNER && $this->business->country == CountryCode::SINGAPORE) {
                // keep not create bank for stripe
            } else {
                $useInStripe = $data['use_in_stripe'];

                if (!$useInStripe) {
                    $useInStripe = $this->business->bankAccounts->where('hitpay_default', true)->count() === 0;
                }

                try {
                    Create::new($this->business->payment_provider)
                        ->setBusiness($this->business)
                        ->handle($bankAccount, $useInStripe);
                } catch (InvalidStateException | AccountNotFoundException $exception) {
                    Facades\Log::info($exception->getMessage());
                } catch (InvalidRequestException $exception) {
                    $response = $exception->getJsonBody();

                    if (isset($response['error']['code'])) {
                        if ($response['error']['code'] === 'account_number_invalid') {
                            throw ValidationException::withMessages([
                                'number' => $response['error']['message'],
                            ]);
                        }

                        if ($response['error']['code'] === 'routing_number_invalid') {
                            throw ValidationException::withMessages([
                                'bank_routing_number' => $response['error']['message'],
                            ]);
                        }
                    }

                    throw $exception;
                }
            }
        }

        return $bankAccount;
    }

    /**
     * Try dry run, but not involving the setting of default account.
     *
     * @return $this
     */
    public function dryRun()
    {
        $this->dryRun = true;

        return $this;
    }
}
