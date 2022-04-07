<?php

namespace HitPay\Stripe\CustomAccount\File;

use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Config;

class Create extends File
{
    protected string $purpose = '';
    protected string $path = '';
    protected ?\App\Business\File $businessFile = null;

    /***
     * @param string $purpose
     * @return $this
     */
    public function setPurpose(string $purpose) : self
    {
        $this->purpose = $purpose;

        return $this;
    }

    /***
     * @param string $path
     * @return $this
     */
    public function setFilepath(string $path) : self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param \App\Business\File $businessFile
     * @return $this
     */
    public function setBusinessFile(\App\Business\File $businessFile) : self
    {
        $this->businessFile = $businessFile;

        return $this;
    }

    /**
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     */
    public function handle()
    {
        $this->getCustomAccount();

        if (!Facades\App::environment('production')) {
            // for testing only we use this example image provided by stripe
            // information link: https://stripe.com/docs/connect/testing#test-document-images
            // $file = Facades\File::get(public_path('stripe/test-mode/images/success.png'));
            // test with file test not worked, adi: Jan 17 2022

            // if still failed, test with file token like here for more info:
            // https://stripe.com/docs/connect/testing#test-file-tokens
            // file_identity_document_success	Uses the verified image and marks that document requirement as satisfied.
            // file_identity_document_failure	Uses the unverified image and marks that document requirement as not satisfied.
            if (Config::get('services.stripe.sg.stripe_custom_account_positive_test_mode')) {
                return json_encode([
                    'id' => 'file_identity_document_success',
                ]);
            } else {
                return json_encode([
                    'id' => 'file_identity_document_failure',
                ]);
            }
        } else {
            $file = Facades\Storage::get($this->path);

            $params = [
                'purpose' => $this->purpose,
                'file' => $file,
                'file_link_data' => [
                    'metadata' => [
                        'business_id' => $this->businessId,
                        'business_file_id' => $this->businessFile ? $this->businessFile->getKey() : null,
                        'platform' => Config::get('app.name'),
                        'environment' => Config::get('app.env'),
                    ]
                ]
            ];

            return \Stripe\File::create($params,[
                'stripe_version' => $this->stripeVersion
            ]);
        }
    }
}
