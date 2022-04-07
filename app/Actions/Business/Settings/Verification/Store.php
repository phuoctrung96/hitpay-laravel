<?php

namespace App\Actions\Business\Settings\Verification;

use App\Business\Verification;
use App\Enumerations\Business\ComplianceRiskLevel;
use App\Enumerations\VerificationStatus;
use App\Jobs\CheckCompliance;
use App\Notifications\NotifyAdminManualVerification;
use Illuminate\Support\Facades;

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
            $rules['shareholders_last_name.*'] = 'nullable|string';
            $rules['shareholders_id_number.*'] = 'required|string';
            $rules['shareholders_dob.*'] = 'required|string';
            $rules['shareholders_address.*'] = 'required|string';
            $rules['shareholders_postal.*'] = 'required|string';
            $rules['shareholders_email.*'] = 'nullable|email';
        }

        Facades\Validator::make($data, $rules)->validate();

        $verificationData = $data;

        if ($this->data['type'] === 'business') {
            $this->validateShareholder($data);
        }

        $verificationData = $this->unsetSubmittedData($verificationData);

        if ($this->data['type'] === 'business') {
            $identification = $data['uen'] ?? null;
            $name = $data['entity_name'] ?? null;
        } else {
            $identification = $data['nric'] ?? null;
            $name = $data['name'] ?? null;
        }

        $verification = $this->business->verifications()->create([
            'type' => $this->data['type'],
            'identification' => $identification,
            'name' => $name,
            'submitted_data' => $verificationData,
            'supporting_documents' => isset($this->supportedDocs) ? json_encode($this->supportedDocs) : null,
            'status' => VerificationStatus::PENDING,
            'identity_documents' => isset($this->identityDocs) ? json_encode($this->identityDocs) : null,
            'business_description' => $data['business_description'] ?? null,
        ]);

        $riskLevel = $this->data['type'] === 'personal'
            ? ComplianceRiskLevel::HIGH_RISK
            : ComplianceRiskLevel::LOW_RISK;

        if (!$this->business->complianceNotes && $this->data['type']) {
            $this->business->complianceNotes()->create([
                'risk_level' => $riskLevel,
            ]);
        }

        $this->business->update([
            'verified_wit_my_info_sg' => true,
        ]);

        $this->updateBusinessAddressFromVerification($verification);

        $this->addPersonToStripeIfRequired();

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

        return $verification->refresh();
    }
}
