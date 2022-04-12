<?php

namespace App\Actions\Business\Settings\Verification;

use App\Actions\Business\Action as BaseAction;
use App\Business\PaymentProvider;
use App\Business\Verification;
use App\Enumerations\Business\Type;
use App\Enumerations\VerificationProvider;
use App\Helpers\StripeCustomAccountHelper;
use Carbon\Carbon;
use Exception;
use HitPay\Stripe\CustomAccount;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

abstract class Action extends BaseAction
{
    use StripeCustomAccountHelper;

    protected array $supportedDocs = [];

    protected array $identityDocs = [];

    protected Verification $verification;

    /**
     * @param Request $request
     * @return $this
     */
    public function withRequestFile(Request $request) : self
    {
        $storageDefaultDisk = Facades\Storage::getDefaultDriver();
        $destination = 'verification-documents/';

        if ($request->has('supporting_documents')) {
            $files = $request->file('supporting_documents');

            foreach ($files as $file) {
                $filename = str_replace('-', '', Str::orderedUuid()->toString()) . '.' . $file->getClientOriginalExtension();
                $path = $destination . $filename;

                $this->supportedDocs[] = $path;

                try {
                    Facades\Storage::disk($storageDefaultDisk)->put($path, file_get_contents($file));
                } catch (Exception $e) {
                    Facades\Log::info('Upload file verification failed. ' . $e->getMessage());
                }
            }
        }

        if ($request->has('identity_front')) {
            $file = $request->file('identity_front');

            $filename = str_replace('-', '', Str::orderedUuid()->toString()) . '.' . $file->getClientOriginalExtension();
            $path = $destination . $filename;

            $this->identityDocs['front'] = $path;

            try {
                Facades\Storage::disk($storageDefaultDisk)->put($path, file_get_contents($file));
            } catch (Exception $e) {
                Facades\Log::info('Upload file verification failed. ' . $e->getMessage());
            }
        }

        if ($request->has('identity_back')) {
            $file = $request->file('identity_back');

            $filename = str_replace('-', '', Str::orderedUuid()->toString()) . '.' . $file->getClientOriginalExtension();
            $path = $destination . $filename;

            $this->identityDocs['back'] = $path;

            try {
                Facades\Storage::disk($storageDefaultDisk)->put($path, file_get_contents($file));
            } catch (Exception $e) {
                Facades\Log::info('Upload file verification failed. ' . $e->getMessage());
            }
        }

        return $this;
    }

    /***
     * @param Verification $verification
     * @return $this
     */
    public function setVerification(Verification $verification) : self
    {
        $this->verification = $verification;

        return $this;
    }

    /**
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validateShareholder(array $data) : void
    {
        $hasOwner = false;
        $hasDirector = false;
        $isOwnerHasEmail = false;
        $isDirectorHasEmail = false;
        $isDobValid = true;

        $shareHoldersOwners = $data['shareholders_is_owner'];
        $shareHoldersEmails = $data['shareholders_email'];
        $shareHoldersDirectors = $data['shareholders_is_director'];
        $shareHoldersTitles = $data['shareholders_title'];
        $shareHoldersDobs = $data['shareholders_dob'];

        foreach ($shareHoldersOwners as $key => $shareHoldersOwner) {
            if ($shareHoldersOwner == "yes") {
                $hasOwner = true;

                if (trim($shareHoldersEmails[$key]) != "") {
                    $isOwnerHasEmail = true;

                    break;
                }
            }
        }

        foreach ($shareHoldersDirectors as $key => $shareHoldersDirector) {
            if ($shareHoldersDirector == "yes") {
                $hasDirector = true;

                if (trim($shareHoldersEmails[$key]) != "") {
                    $isDirectorHasEmail = true;

                    break;
                }
            }
        }

        // https://support.stripe.com/questions/age-requirement-to-create-a-stripe-account
        $min = 13;
        $minimumDate = Carbon::now()->subYear($min)->format('Y-m-d');

        foreach ($shareHoldersDobs as $shareHoldersDob) {
            if ($minimumDate <= $shareHoldersDob) {
                $isDobValid = false;
                break;
            }
        }

        if (!$isDobValid) {
            throw ValidationException::withMessages([
                'shareholders_dob' => 'Must be at least 13 years of age to use Stripe',
            ]);
        }

        if (!$hasOwner) {
            throw ValidationException::withMessages([
                'shareholders_is_owner' => 'No have owner of shareholders',
            ]);
        }

        if (!$hasDirector) {
            throw ValidationException::withMessages([
                'shareholders_is_director' => 'No have director of shareholders',
            ]);
        }

        if (!$isOwnerHasEmail) {
            throw ValidationException::withMessages([
                'shareholders_is_owner' => 'No have email owner of shareholders',
            ]);
        }

        if (!$isDirectorHasEmail) {
            throw ValidationException::withMessages([
                'shareholders_is_director' => 'No have email director of shareholders',
            ]);
        }
    }

    /***
     * @param array $verificationData
     * @return array
     */
    protected function unsetSubmittedData(array $verificationData) : array
    {
        unset($verificationData['shareholders_count']);
        unset($verificationData['shareholders_error']);
        unset($verificationData['shareholders_first_name']);
        unset($verificationData['shareholders_first_name_error']);
        unset($verificationData['shareholders_last_name']);
        unset($verificationData['shareholders_last_name_error']);
        unset($verificationData['shareholders_id_number']);
        unset($verificationData['shareholders_id_number_error']);
        unset($verificationData['shareholders_is_director']);
        unset($verificationData['shareholders_is_owner']);
        unset($verificationData['shareholders_is_executive']);
        unset($verificationData['shareholders_dob']);
        unset($verificationData['shareholders_dob_error']);
        unset($verificationData['shareholders_address']);
        unset($verificationData['shareholders_address_error']);
        unset($verificationData['shareholders_postal']);
        unset($verificationData['shareholders_postal_error']);
        unset($verificationData['shareholders_email']);
        unset($verificationData['shareholders_email_error']);
        unset($verificationData['shareholders_relationship_error']);
        unset($verificationData['shareholders_title']);
        unset($verificationData['shareholders_title_error']);
        unset($verificationData['shareholders_is_executive']);

        return $verificationData;
    }

    /**
     * @param Verification $verification
     * @param array $verification_data
     * @return array
     */
    protected function setShareholderData(Verification $verification, array $verification_data)
    {
        if (isset($verification_data['shareholders'])) {
            $shareholders = $verification_data['shareholders'];
        } else {
            $shareholders = [];
        }

        $verification_data['shareholders_count'] = count($shareholders);
        $verification_data['shareholders_first_name'] = [];
        $verification_data['shareholders_first_name_error'] = [];
        $verification_data['shareholders_last_name'] = [];
        $verification_data['shareholders_last_name_error'] = [];
        $verification_data['shareholders_error'] = [];
        $verification_data['shareholders_id_number'] = [];
        $verification_data['shareholders_id_number_error'] = [];
        $verification_data['shareholders_is_director'] = [];
        $verification_data['shareholders_is_owner'] = [];
        $verification_data['shareholders_is_executive'] = [];
        $verification_data['shareholders_dob'] = [];
        $verification_data['shareholders_dob_error'] = [];
        $verification_data['shareholders_address'] = [];
        $verification_data['shareholders_address_error'] = [];
        $verification_data['shareholders_postal'] = [];
        $verification_data['shareholders_postal_error'] = [];
        $verification_data['shareholders_title'] = [];
        $verification_data['shareholders_title_error'] = [];
        $verification_data['shareholders_email'] = [];
        $verification_data['shareholders_email_error'] = [];
        $verification_data['shareholders_relationship_error'] = [];

        for ($i=0; $i<$verification_data['shareholders_count']; $i++) {
            $verification_data['shareholders_first_name'][] = "";
            $verification_data['shareholders_first_name_error'][] = "";
            $verification_data['shareholders_last_name'][] = "";
            $verification_data['shareholders_last_name_error'][] = "";
            $verification_data['shareholders_error'][] = "";
            $verification_data['shareholders_id_number'][] = "";
            $verification_data['shareholders_id_number_error'][] = "";
            $verification_data['shareholders_is_director'][] = 'no';
            $verification_data['shareholders_is_owner'][] = 'no';
            $verification_data['shareholders_is_executive'][] = 'no';
            $verification_data['shareholders_dob'][] = "";
            $verification_data['shareholders_dob_error'][] = "";
            $verification_data['shareholders_address'][] = "";
            $verification_data['shareholders_address_error'][] = "";
            $verification_data['shareholders_postal'][] = "";
            $verification_data['shareholders_postal_error'][] = "";
            $verification_data['shareholders_title'][] = "";
            $verification_data['shareholders_title_error'][] = "";
            $verification_data['shareholders_email'][] = "";
            $verification_data['shareholders_email_error'][] = "";
            $verification_data['shareholders_relationship_error'][] = "";
        }

        $businessPersons = $verification->persons()->get();

        if ($businessPersons->count() > 0) {
            $totalPersonWithoutRepresentative = $businessPersons->count() - 1;

            if ($totalPersonWithoutRepresentative == count($shareholders)) {
                // generate data for stripe
                foreach ($businessPersons as $keyIndex => $businessPerson) {
                    $relationships = $businessPerson->relationship;

                    if (
                        is_array($relationships) &&
                        array_key_exists('representative', $relationships) &&
                        $relationships['representative'] == true) {
                        // skip adding user representative to view
                        continue;
                    }

                    $verification_data['shareholders_first_name'][$keyIndex] = $businessPerson->first_name;
                    $verification_data['shareholders_last_name'][$keyIndex] = $businessPerson->last_name;
                    $verification_data['shareholders_id_number'][$keyIndex] = $businessPerson->id_number;
                    $verification_data['shareholders_dob'][$keyIndex] = $businessPerson->dob;
                    $verification_data['shareholders_address'][$keyIndex] = $businessPerson->address;
                    $verification_data['shareholders_postal'][$keyIndex] = $businessPerson->postal_code;
                    $verification_data['shareholders_title'][$keyIndex] = $businessPerson->title;
                    $verification_data['shareholders_email'][$keyIndex] = $businessPerson->email;

                    $verification_data['shareholders_is_owner'][$keyIndex] = 'no';
                    $verification_data['shareholders_is_director'][$keyIndex] = 'no';
                    $verification_data['shareholders_is_executive'][$keyIndex] = 'no';

                    if (is_array($relationships)) {
                        foreach ($relationships as $key => $relationship) {
                            if ($key == 'owner' && $relationship === true) {
                                $verification_data['shareholders_is_owner'][$keyIndex] = 'yes';
                            }

                            if ($key == 'director' && $relationship === true) {
                                $verification_data['shareholders_is_director'][$keyIndex] = 'yes';
                            }

                            if ($key == 'executive' && $relationship === true) {
                                $verification_data['shareholders_is_executive'][$keyIndex] = 'yes';
                            }
                        }
                    }
                }
            }
        }

        return $verification_data;
    }

    /**
     * @param Verification $verification
     * @return void
     */
    protected function updateBusinessAddressFromVerification(Verification $verification) : void
    {
        if ($this->business->business_type === Type::INDIVIDUAL) {
            if ($verification->verification_provider === VerificationProvider::MYINFO) {
                if ($verification->my_info_data) {
                    $personData = $verification->type === 'business' ? $verification->my_info_data['data']['person'] : $verification->my_info_data['data'];

                    $companyAddress = Arr::only($personData['regadd'], ['unit', 'block', 'floor', 'postal', 'street', 'country']);

                    $this->business->update([
                        'street' => $companyAddress['street']['value'] ?? $companyAddress['street']['desc'] ?? $companyAddress['street'] ?? null,
                        'postal_code' => $companyAddress['postal']['value'] ?? $companyAddress['street']['desc'] ?? $companyAddress['street'] ?? null,
                    ]);
                }

                if (empty($verification->my_info_data) && $verification->submitted_data) {
                    $addressData = Arr::only($verification->submitted_data, ['regadd']);

                    $this->business->update([
                        'street' => $addressData['regadd'] ?? null
                    ]);
                }
            } else {
                // handle cognito or manual input
                if ($verification->submitted_data) {
                    $addressData = Arr::only($verification->submitted_data, ['regadd']);

                    $this->business->update([
                        'street' => $addressData['regadd'] ?? null
                    ]);
                }
            }
        } else {
            if ($verification->verification_provider === VerificationProvider::MYINFO) {
                if ($verification->my_info_data) {
                    $entityAddress = $verification->my_info_data['data']['entity']['addresses']['addresses-list'][0] ?? [];

                    $companyAddress = Arr::only($entityAddress, ['unit', 'block', 'floor', 'postal', 'street', 'country']);

                    $this->business->update([
                        'street' => $companyAddress['street']['value'] ?? null,
                        'postal_code' => $companyAddress['postal']['value'] ?? null,
                    ]);
                }

                if (empty($verification->my_info_data) && $verification->submitted_data) {
                    $addressData = Arr::only($verification->submitted_data, ['address']);

                    $this->business->update([
                        'street' => $addressData['address'] ?? null
                    ]);
                }
            } else {
                // handle cognito and manual input
                if ($verification->submitted_data) {
                    $addressData = Arr::only($verification->submitted_data, ['address']);

                    $this->business->update([
                        'street' => $addressData['address'] ?? null
                    ]);
                }
            }
        }
    }

    /**
     * Add person to Stripe if required.
     *
     * @return void
     */
    protected function addPersonToStripeIfRequired() : void
    {
        try {
            CustomAccount\Update::new($this->business->payment_provider)->setBusiness($this->business)->handle();
        } catch (InvalidStateException $exception) {
            Facades\Log::info($exception->getMessage());
        } catch (Exception $exception) {
            Facades\Log::warning(
                "Error when updating Stripe account for business (ID : {$this->businessId}). Got error: {$exception->getMessage()}"
            );
        }
    }
}
