<?php

namespace App\Actions\Business\Settings\BankAccount;

use App\Enumerations\Business\Type as BusinessType;
use App\Enumerations\CountryCode;
use HitPay\Data\Countries;
use HitPay\Stripe\CustomAccount\ExternalAccount;
use Illuminate\Support\Facades;
use Illuminate\Validation\Rule;

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
        $banksData = Countries::get($this->business->country)->banks();

        $rules['bank_swift_code'] = [ 'required', Rule::in($banksData->pluck('swift_code')) ];

        if ($this->business->country === CountryCode::SINGAPORE) {
            $rules['branch_code'][] = 'required_with:bank_swift_code';

            $_bankSwiftCode = $data['bank_swift_code'] ?? null;

            if (is_string($_bankSwiftCode)) {
                /** @var \HitPay\Data\Countries\Objects\Bank $_bank */
                $_bank = $banksData->where('swift_code', $_bankSwiftCode)->first();

                if ($_bank && $_bank->useBranch) {
                    $rules['branch_code'][] = Rule::in($_bank->branches->pluck('code')->toArray());
                }
            }
        }

        $rules['number'] = 'required|digits_between:4,32';
        $rules['holder_name'] = 'required|string';
        $rules['holder_type'] = [ 'required', Rule::in([ BusinessType::COMPANY, BusinessType::INDIVIDUAL ]) ];
        $rules['use_in_hitpay'] = 'required|bool';
        $rules['use_in_stripe'] = 'required|bool';
        $rules['remark'] = 'nullable|string|max:255';

        $data = Facades\Validator::validate($this->data, $rules);

        /** @var \HitPay\Data\Countries\Objects\Bank $bank */
        $bank = $banksData->where('swift_code', $data['bank_swift_code'])->first();

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

        $this->bankAccount->bank_swift_code = $data['bank_swift_code'];
        $this->bankAccount->bank_routing_number = $bankRoutingNumber;
        $this->bankAccount->number = $data['number'];
        $this->bankAccount->holder_name = $data['holder_name'];
        $this->bankAccount->holder_type = $data['holder_type'];
        $this->bankAccount->remark = $data['remark'] ?? null;

        Facades\DB::transaction(function () {
            $this->bankAccount->save();
        });

        if ($this->business->usingStripeCustomAccount()) {
            if ($this->bankAccount->stripe_external_account_id === null) {
                ExternalAccount\Create::new($this->business->payment_provider)
                    ->setBusiness($this->business)
                    ->handle($this->bankAccount, $this->bankAccount->stripe_external_account_default);
            } elseif ($this->bankAccount->wasChanged('routing_number', 'number')) {
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
        }

        return true;
    }
}
