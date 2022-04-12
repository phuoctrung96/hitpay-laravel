<?php

namespace App\Actions\Business\Stripe\VerificationOnboard;

use App\Business;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Facades;
use HitPay\Stripe\CustomAccount\File;

class UpdateBusinessCompany extends Action
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

            // $this->uploadDocumentFile($businessPaymentProvider);

            $businessPaymentProvider = $this->updateAccount();

            Facades\DB::commit();

            return $businessPaymentProvider;
        } catch (\Exception $exception) {
            Facades\DB::rollback();

            throw $exception;
        }
    }

    /**
     * @param Business\PaymentProvider $businessPaymentProvider
     * @return void
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function uploadDocumentFile(
        Business\PaymentProvider $businessPaymentProvider
    ) : void
    {
        if (count($this->supportedDocs) === 0) {
            return;
        }

        $handler = File\Create::new($this->business->payment_provider)
            ->setBusiness($this->business);

        try {
            $handler->getCustomAccount();
        } catch (GeneralException $exception) {
            if ($exception instanceof InvalidStateException) {
                Facades\Log::critical("Trying but unable to create a file for the business (ID : {$this->businessId}) which is not using Stripe custom account.");
            } elseif ($exception instanceof AccountNotFoundException) {
                Facades\Log::critical("Trying but unable to create a file for a non-Stripe custom connected business account (ID : {$this->businessId}).");
            }

            throw $exception;
        }

        $businessPaymentProvider->files()->detach();

        foreach ($this->supportedDocs as $doc) {
            $businessFile = new Business\File();
            $businessFile->business_id = $this->business->getKey();
            $businessFile->group = $doc['group'];
            $businessFile->media_type = $doc['media_type'];
            $businessFile->disk = $doc['disk'];
            $businessFile->path = $doc['path'];
            $businessFile->original_name = $doc['original_name'];
            $businessFile->extension = $doc['extension'];
            $businessFile->storage_size = $doc['storage_size'];
            $businessFile->remark = $doc['remark'];
            $businessFile->save();

            $businessFile->paymentProviders()->attach($businessPaymentProvider->getKey());

            // submit to stripe file
            // document: https://stripe.com/docs/api/accounts/object#account_object-company-verification-document-back
            $responseHandler = $handler->setPurpose('additional_verification')
                ->setFilepath($businessFile->path)
                ->setBusinessFile($businessFile)
                ->handle();

            $responseFile = json_decode($responseHandler, true);

            $businessFile->stripe_file_id = $responseFile['id'];
            $businessFile->data = $responseFile;
            $businessFile->save();
        }
    }
}
