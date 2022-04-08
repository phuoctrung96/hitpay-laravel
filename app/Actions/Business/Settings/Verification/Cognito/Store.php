<?php

namespace App\Actions\Business\Settings\Verification\Cognito;

use App\Business\Verification;
use App\Enumerations\VerificationStatus;
use App\Exceptions\HitPayLogicException;
use App\Jobs\CheckCompliance;
use App\Notifications\NotifyAdminManualVerification;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Support\Facades;
use Psr\SimpleCache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Throwable;

class Store extends Action
{
    /**
     * @return Verification
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function process() : Verification
    {
        $data = json_decode($this->data['verification'], true);

        $rules = [
            'nric' => 'nullable|string',
            'name' => 'nullable|string',
            'sex' => 'nullable|string',
            'residentialstatus' => 'nullable|string',
            'nationality' => 'nullable|string',
            'dob' => 'nullable|string',
            'regadd' => 'nullable|string',
            'email' => 'nullable|string',
            'business_description' => 'nullable|string|max:1000',
        ];

        if ($this->data['type'] === 'business') {
            $rules['uen'] = 'nullable|string';
            $rules['entity_name'] = 'nullable|string';
            $rules['entity_type'] = 'nullable|string';
            $rules['entity_status'] = 'nullable|string';
            $rules['registration_date'] = 'nullable|string';
            $rules['primary_activity-desc'] = 'nullable|string';
            $rules['address'] = 'nullable|string';
            $rules['shareholders.*'] = 'required|string';
            $rules['shareholders_first_name.*'] = 'required|string';
            $rules['shareholders_last_name.*'] = 'required|string';
            $rules['shareholders_id_number.*'] = 'required|string';
            $rules['shareholders_dob.*'] = 'required|string';
            $rules['shareholders_address.*'] = 'required|string';
            $rules['shareholders_postal.*'] = 'required|string';
            $rules['shareholders_email.*'] = 'nullable|email';
        }

        Facades\Validator::make($data, $rules)->validate();

        if ($this->data['type'] === 'business') {
            $this->validateShareholder($data);
        }

        $verificationData = $data;

        $verificationData = $this->unsetSubmittedData($verificationData);

        if ($this->data['type'] === 'business') {
            $identification = $data['uen'] ?? null;
            $name = $data['entity_name'] ?? null;
        } else {
            $identification = $data['nric'] ?? null;
            $name = $data['name'] ?? null;
        }

        $verification = $this->verification;

        $verification->update([
            'type' => $this->data['type'],
            'identification' => $identification,
            'name' => $name,
            'submitted_data' => $verificationData,
            'supporting_documents' => isset($this->supportedDocs) ? json_encode($this->supportedDocs) : null,
            'identity_documents' => isset($this->identityDocs) ? json_encode($this->identityDocs) : null,
            'business_description' => $data['business_description'] ?? null,
            'status' => VerificationStatus::PENDING,
        ]);

        $this->business->update([
            'verified_wit_my_info_sg' => true,
        ]);

        $this->updateBusinessAddressFromVerification($verification);

        // set queue job on process
        $this->updateStripeInit();

        \App\Jobs\Business\Stripe\Person\CreatePersonFromVerificationJob::dispatch($verification, $data);

        if (!Facades\App::environment('production')) {
            try {
                CheckCompliance::dispatch($verification);

                Facades\Notification::route('mail', ['compliance@hit-pay.com'])
                    ->notify(new NotifyAdminManualVerification($this->business));
            } catch (\Exception $e) {
                Facades\Log::critical("Trying but unable to dispatch CheckCompliance and NotifyAdminManualVerification for the business (ID : {$this->businessId}) which is not using Stripe custom account.");
            }
        } else {
            CheckCompliance::dispatch($verification);

            Facades\Notification::route('mail', ['compliance@hit-pay.com'])
                ->notify(new NotifyAdminManualVerification($this->business));
        }

        return $verification;
    }
}
