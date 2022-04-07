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
        // TODO - WARNING
        //   -------------->>>
        //   We DO NOT ALLOW users to have same currency bank account in the same country for the time being. Because
        //   it will take time to create this feature in Stripe and our dashboard. Keep this in view.
        //   -
        //   For now, the bank account will be always country Singapore + currency SGD, so if the business already
        //   have a bank account, we will return error. We will have to update this later when multi currency bank
        //   account is introduced.

        if (!in_array($this->business->country, [CountryCode::SINGAPORE, CountryCode::MALAYSIA])) {
            throw new BadRequest('Setting up bank account for non-Singapore and non-Malaysia based businesses is currently not supported.');
        }

        $currencyCodesData = $this->business->currenciesAvailable();
        $banksData = $this->business->banksAvailable();

        $rules['currency'] = [ 'required', Rule::in($currencyCodesData) ];
        $rules['bank_id'] = [ 'required', Rule::in($banksData->pluck('id')) ];

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

        // We should validate the account number from different banks in the future.
        //
        $rules['number'] = 'required|digits_between:4,32';
        $rules['holder_name'] = 'required|string';
        $rules['holder_type'] = [ 'required', Rule::in([ BusinessType::COMPANY, BusinessType::INDIVIDUAL, BusinessType::PARTNER ]) ];
        $rules['use_in_hitpay'] = 'required|bool';
        $rules['use_in_stripe'] = 'required|bool';
        $rules['remark'] = 'nullable|string|max:255';

        $data = Facades\Validator::validate($this->data, $rules);

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

        $bankAccount = new BankAccount;

        // Currently, we allow bank account from the same country of the business only.
        //
        $bankAccount->country = $this->business->country;

        $bankAccount->currency = $data['currency'];
        $bankAccount->bank_swift_code = $bank->swift_code;
        $bankAccount->bank_routing_number = $bankRoutingNumber;
        $bankAccount->number = $data['number'];

        $bankData = $bank->toArray();

        unset($bankData['branches']);

        $bankAccount->data = [
            'data' => [
                'bank' => $bankData,
            ],
        ];
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
