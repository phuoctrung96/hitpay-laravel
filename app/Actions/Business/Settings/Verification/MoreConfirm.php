<?php

namespace App\Actions\Business\Settings\Verification;

use App\Business\Verification;
use Illuminate\Support\Facades;

class MoreConfirm extends Action
{
    /**
     * @return Verification
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function process() : Verification
    {
        $isNotMyInfo = ( $this->data['fill_type'] ?? 'myinfo' ) !== 'myinfo';

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
            'business_description' => 'nullable|string',
        ];

        if ($this->data['type'] === 'business') {
            $rules['uen'] = 'nullable|string';
            $rules['entity_name'] = 'nullable|string';
            $rules['entity_type'] = 'nullable|string';
            $rules['entity_status'] = 'nullable|string';
            $rules['registration_date'] = 'nullable|string';
            $rules['primary_activity-desc'] = 'nullable|string';
            $rules['address'] = 'nullable|string';

            // Maybe this will cause some issue for cognito
            //
            if ($isNotMyInfo) {
                $rules['shareholders.*'] = 'required|string';
                $rules['shareholders_first_name.*'] = 'required|string';
                $rules['shareholders_last_name.*'] = 'required|string';
                $rules['shareholders_id_number.*'] = 'required|string';
                $rules['shareholders_dob.*'] = 'required|string';
                $rules['shareholders_address.*'] = 'required|string';
                $rules['shareholders_postal.*'] = 'required|string';
                $rules['shareholders_email.*'] = 'nullable|email';
            }
        }

        $data = Facades\Validator::make($data, $rules)->validate();

        if ($isNotMyInfo && $this->data['type'] === 'business') {
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
            'verified_at' => $verification->freshTimestamp(),
            'identity_documents' => isset($this->identityDocs) ? json_encode($this->identityDocs) : null,
            'supporting_documents' => isset($this->supportedDocs) ? json_encode($this->supportedDocs) : null,
            'business_description' => $data['business_description'] ?? null,
        ]);

        $this->business->verified_wit_my_info_sg = true;

        $this->business->save();

        $this->updateBusinessAddressFromVerification($verification);

        $this->addPersonToStripeIfRequired();

        return $verification;
    }
}
