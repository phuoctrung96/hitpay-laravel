<?php

namespace App\Actions\Business\Stripe\VerificationOnboard;

use App\Actions\Business\Action as BaseAction;
use App\Business\PaymentProvider;
use App\Helpers\StripeCustomAccountHelper;
use Exception;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use HitPay\Stripe\CustomAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades;
use App\Business;
use Stripe\Person;

abstract class Action extends BaseAction
{
    use StripeCustomAccountHelper;

    protected array $supportedDocs = [];

    /***
     * @return PaymentProvider
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    protected function updateAccount() : PaymentProvider
    {
        $handler = CustomAccount\Update::new($this->business->payment_provider)
            ->setBusiness($this->business);

        try {
            $handler->getCustomAccount();
        } catch (GeneralException $exception) {
            if ($exception instanceof InvalidStateException) {
                Facades\Log::critical("Trying but unable to create a bank account for the business (ID : {$this->businessId}) which is not using Stripe custom account.");
            } elseif ($exception instanceof AccountNotFoundException) {
                Facades\Log::critical("Trying but unable to create a bank account for a non-Stripe custom connected business account (ID : {$this->businessId}).");
            }

            throw $exception;
        }

        return $handler->handle();
    }

    /**
     * @param Request $request
     * @param string $group
     * @return Action
     * @throws Exception
     */
    public function withRequestFile(Request $request, string $group = 'stripe_file') : self
    {
        $files = $request->file('supporting_documents');

        $storageDefaultDisk = Facades\Storage::getDefaultDriver();
        $destination = 'stripe-documents/';

        foreach ($files as $file) {
            $filename = str_replace('-', '', Str::orderedUuid()->toString()) . '.' . $file->getClientOriginalExtension();
            $path = $destination . $filename;

            $fileParams = [];

            $fileParams['group'] = $group;
            $fileParams['disk'] = $storageDefaultDisk;
            $fileParams['path'] = $path;

            $fileParams['media_type'] = $file->getClientMimeType();
            $fileParams['original_name'] = $file->getClientOriginalName();
            $fileParams['extension'] = $file->getClientOriginalExtension();
            $fileParams['storage_size'] = $file->getSize();
            $fileParams['remark'] = '';

            try {
                Facades\Storage::disk($storageDefaultDisk)->put($path, file_get_contents($file));

                $this->supportedDocs[] = $fileParams;
            } catch (Exception $exception) {
                Facades\Log::info('Upload file verification failed. ' . $exception->getMessage());

                throw $exception;
            }
        }

        return $this;
    }

    /***
     * @param Business\Person $businessPerson
     * @return Person
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function getPerson(Business\Person $businessPerson) : Person
    {
        $handler = CustomAccount\Person\Retrieve::new($this->business->payment_provider)
            ->setBusiness($this->business);

        try {
            $handler->getCustomAccount();
        } catch (GeneralException $exception) {
            if ($exception instanceof InvalidStateException) {
                Facades\Log::critical("Trying but unable to create a person for the business (ID : {$this->businessId}) which is not using Stripe custom account.");
            } elseif ($exception instanceof AccountNotFoundException) {
                Facades\Log::critical("Trying but unable to create a person for a non-Stripe custom connected business account (ID : {$this->businessId}).");
            }

            throw $exception;
        }

        return $handler->handle($businessPerson);
    }
}
