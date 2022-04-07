<?php

namespace App\Http\Resources\Business;

use App\Business\Transfer;
use App\Models\Business;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Business\BankAccount
 * @mixin \HitPay\Business\Ownable
 */
class BankAccount extends JsonResource
{
    public function __construct(Business\BankAccount $resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request) : array
    {
        $data['id'] = $this->getKey();

        $data['business_id'] = $this->business_id;

        $data['country'] = $this->country;
        $data['country_name'] = get_country_name($this->country);

        $data['currency'] = $this->currency;

        $data['bank_swift_code'] = $this->bank_swift_code;
        $data['bank_routing_number'] = $this->bank_routing_number;
        $data['bank_name'] = Transfer::$availableBankSwiftCodes[$data['bank_swift_code']] ?? null;

        $data['number'] = $this->number;

        $data['holder_name'] = $this->holder_name;
        $data['holder_type'] = $this->holder_type;

        $data['remark'] = $this->remark;

        $data['is_for_hitpay'] = true;
        $data['is_for_hitpay_default'] = $this->hitpay_default;
        $data['is_for_stripe'] = $this->stripe_external_account_id !== null;
        $data['is_for_stripe_default'] = $this->stripe_external_account_default;

        $data['is_deleted'] = $this->trashed();

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();
        $data['deleted_at'] = $data['is_deleted'] ? $this->deleted_at->toAtomString() : null;

        return $data;
    }
}
