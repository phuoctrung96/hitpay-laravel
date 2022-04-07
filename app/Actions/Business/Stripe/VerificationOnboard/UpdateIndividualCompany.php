<?php

namespace App\Actions\Business\Stripe\VerificationOnboard;

use App\Business;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Facades;

class UpdateIndividualCompany extends Action
{
    /***
     * @return Business\PaymentProvider
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function process() : Business\PaymentProvider
    {
        $businessPaymentProviderRequest = json_decode($this->data['businessPaymentProvider'], true);

        $businessPaymentProvider = $this->business->paymentProviders()
            ->where('id', $businessPaymentProviderRequest['id'])
            ->first();

        if ($businessPaymentProvider === null) {
            throw new \Exception('Payment provider not found from business ' . $this->business->getKey());
        }

        $companyParams = $businessPaymentProviderRequest['data']['account']['company'];
        $businessProfileParams = $businessPaymentProviderRequest['data']['account']['business_profile'];

        $params = [
            'business_postal_code' => $companyParams['address']['postal_code'] ?? '',
            'business_city' => $companyParams['address']['city'] ?? '',
            'business_state' => $companyParams['address']['state'] ?? '',
            'business_line1' => $companyParams['address']['line1'] ?? '',
            'business_phone' => $companyParams['phone'] ?? '',
            'business_url' => $businessProfileParams['url'] ?? '',
            'business_name' => $companyParams['name'] ?? '',
        ];

        $rules = [
            'business_postal_code' => 'required|string',
            'business_city' => 'nullable|string',
            'business_state' => 'nullable|string',
            'business_line1' => 'required|string',
            'business_phone' => 'required|string',
            'business_url' => 'required|string',
            'business_name' => 'nullable|string'
        ];

        Facades\Validator::make($params, $rules)->validate();

        Facades\DB::beginTransaction();

        try {
            $this->business->postal_code = $params['business_postal_code'];
            $this->business->city = $params['business_city'];
            $this->business->state = $params['business_state'];
            $this->business->street = $params['business_line1'];
            $this->business->phone_number = $params['business_phone'];
            $this->business->website = $params['business_url'];

            if (isset($params['business_name']) && $params['business_name'] != "") {
                $this->business->name = $params['business_name'];
            }

            $this->business->save();

            $businessPaymentProvider = $this->updateAccount();

            Facades\DB::commit();

            return $businessPaymentProvider;
        } catch (\Exception $exception) {
            Facades\DB::rollback();

            throw $exception;
        }
    }
}
