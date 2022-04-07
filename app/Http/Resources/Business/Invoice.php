<?php

namespace App\Http\Resources\Business;

use App\Enumerations\CurrencyCode;
use App\Helpers\Country;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Business\Invoice
 */
class Invoice extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|mixed
     * @throws \ReflectionException
     */
    public function toArray($request)
    {
        $data['id'] = $this->getKey();
        $data['business_id'] = $this->business_id;
        $data['business_customer_id'] = $this->business_customer_id ?? null;
        $data['payment_request_id'] = $this->payment_request_id ?? null;
        $data['reference'] = $this->reference ?? null;
        $data['invoice_number'] = $this->invoice_number ?? null;
        $data['email'] = $this->email ?? null;
        $data['status'] = $this->getCustomStatus();
        $data['currency'] = $this->currency ?? null;
        $data['zero_decimal_cur'] = CurrencyCode::ZERO_DECIMAL_CURRENCIES;
        $data['amount'] = getReadableAmountByCurrency($this->currency, $this->amount);

        $data['customer'] = $this->customer;
        $data['countries'] = Country::getCountries();

        if ($this->tax_setting) {
            $data['tax_setting']['id'] = $this->tax_setting->getKey();
            $data['tax_setting']['name'] = $this->tax_setting->name;
            $data['tax_setting']['rate'] = $this->tax_setting->rate;
        }else{
            $data['tax_setting'] = null;
        }

        $data['amount_no_tax'] = getReadableAmountByCurrency($this->currency, $this->amount_no_tax);

        $products = null;

        if (is_array($this->products)) {
            $products = $this->products;
        }

        $data['products'] = $products;

        $data['invoice_type'] = $products == null ? 'payment_by_fixed_amount' : 'payment_by_product';

        $data['memo'] = $this->memo ?? null;
        $data['attached_file'] = $this->attached_file ?? null;

        $data['created_at'] = $this->created_at->toAtomString();
        $data['updated_at'] = $this->updated_at->toAtomString();
        $data['invoice_date'] = $this->invoice_date ?? null;
        $data['due_date'] = $this->due_date ?? null;

        $allowPartialPayment = $this->allow_partial_payments ?? false;

        $data['allow_partial_payments'] = $allowPartialPayment;

        if ($allowPartialPayment) {
            $data['partial_payments'] = $this->invoicePartialPaymentRequests->load('paymentRequest');
        }

        $data['invoice_link'] = route('invoice.hosted.show', [$this->business_id, $this->id]);

        return $data;
    }
}
