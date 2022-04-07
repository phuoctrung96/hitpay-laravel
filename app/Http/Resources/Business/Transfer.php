<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Business\Transfer
 */
class Transfer extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        [
            $bankSwiftCode,
            $bankAccountNumber,
        ] = explode('@', $this->payment_provider_account_id);

        return [
            'id' => $this->id,
            'bank_name' => \App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode] ?? $bankSwiftCode,
            'bank_switf_code' => $bankSwiftCode,
            'bank_account_number' => $bankAccountNumber,
            'currency' => $this->currency,
            'amount' => getReadableAmountByCurrency($this->currency, $this->amount),
            'remark' => $this->remark,
            'status' => $this->status === 'succeeded_manually' ? 'succeeded' : $this->status,
            'source_type' => $this->source_type,
            'created_at' => $this->created_at->toAtomString(),
        ];
    }
}
