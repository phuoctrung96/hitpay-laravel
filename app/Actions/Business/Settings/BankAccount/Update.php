<?php

namespace App\Actions\Business\Settings\BankAccount;

use App\Enumerations\Business\Type as BusinessType;
use App\Enumerations\CountryCode;
use App\Notifications\NotifyOwnerAboutBankUpdate;
use HitPay\Stripe\CustomAccount\ExternalAccount;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\InvalidRequestException;

class Update extends Action
{
    /**
     * Update the bank account.
     *
     * @return bool
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function process() : bool
    {
        $banksData = $this->business->banksAvailable();

        $shouldUseBanksList = in_array($this->business->country, [CountryCode::SINGAPORE, CountryCode::MALAYSIA]);

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

        if ($this->business->country === CountryCode::SINGAPORE) {
            $rules['branch_code'][] = 'required_with:bank_id';

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
        $rules['holder_type'] = [ 'required', Rule::in([ BusinessType::COMPANY, BusinessType::INDIVIDUAL ]) ];
        $rules['use_in_hitpay'] = 'required|bool';
        $rules['use_in_stripe'] = 'required|bool';
        $rules['remark'] = 'nullable|string|max:255';

        // TODO -
        //   We shouldn't check user password in action class. The action class should be able to used everywhere,
        //   e.g.
        //   1. A user can call this action to update the bank account of his / her business,
        //   2. An admin dashboard can call this action to update the bank account of a business.
        //   3. A command can be created to call this action to update the bank account of a business.
        //
        $rules['password'] = 'required|string';

        $data = Facades\Validator::validate($this->data, $rules);

        if (!Hash::check($data['password'], Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password is incorrect.',
            ]);
        }

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

        $this->bankAccount->bank_swift_code = $bankSwiftCode;
        $this->bankAccount->bank_routing_number = $bankRoutingNumber;
        $this->bankAccount->number = $data['number'];

        if ($shouldUseBanksList) {
            $bankAccountData = $this->bankAccount->data;

            $bankData = $bank->toArray();

            unset($bankData['branches']);

            $bankAccountData['data']['bank'] = $bankData;

            $this->bankAccount->data = $bankAccountData;
        }

        $this->bankAccount->holder_name = $data['holder_name'];
        $this->bankAccount->holder_type = $data['holder_type'];
        $this->bankAccount->remark = $data['remark'] ?? null;

        Facades\DB::transaction(function () {
            $this->bankAccount->save();
        });

        if ($this->business->usingStripeCustomAccount()) {
            try {
                if ($this->bankAccount->stripe_external_account_id === null) {
                    ExternalAccount\Create::new($this->business->payment_provider)
                        ->setBusiness($this->business)
                        ->handle($this->bankAccount, $this->bankAccount->stripe_external_account_default);
                } elseif ($this->bankAccount->wasChanged('bank_routing_number', 'number')) {
                    $existingStripeExternalAccountId = $this->bankAccount->stripe_external_account_id;

                    ExternalAccount\Create::new($this->business->payment_provider)
                        ->setBusiness($this->business)
                        ->handle($this->bankAccount, $this->bankAccount->stripe_external_account_default);

                    ExternalAccount\Delete::new($this->business->payment_provider)
                        ->setBusiness($this->business)
                        ->justDelete($existingStripeExternalAccountId);
                } else {
                    ExternalAccount\Update::new($this->business->payment_provider)
                        ->setBusiness($this->business)
                        ->handle($this->bankAccount, $this->bankAccount->stripe_external_account_default);
                }
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

        $owner = $this->business->owner()->first();
        $owner->notify(new NotifyOwnerAboutBankUpdate);

        return true;
    }
}
