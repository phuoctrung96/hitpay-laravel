<?php

namespace App\Actions\Business\Settings\Verification;

use App\Actions\Business\Action as BaseAction;
use App\Business\PaymentProvider;
use App\Business\Verification;
use App\Enumerations\Business\Type;
use App\Enumerations\VerificationProvider;
use App\Helpers\StripeCustomAccountHelper;
use Carbon\Carbon;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use HitPay\Stripe\CustomAccount\Person;
use HitPay\Stripe\CustomAccount;

abstract class Action extends BaseAction
{
    use StripeCustomAccountHelper;

    protected array $supportedDocs = [];
    protected array $identityDocs = [];
    protected Verification $verification;
    protected bool $isRepresentativePerson = false;
    protected PaymentProvider $businessPaymentProvider;

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
                } catch (\Exception $e) {
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
            } catch (\Exception $e) {
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
            } catch (\Exception $e) {
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
     * @param bool $isRepresentative
     * @return $this
     */
    protected function isRepresentativePerson(bool $isRepresentative)
    {
        $this->isRepresentativePerson = $isRepresentative;

        return $this;
    }

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

    /***
     * @param Verification $verification
     * @param array $requestData
     * @return bool
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws ValidationException
     */
    protected function createPersonFromVerification(Verification $verification, array $requestData) : bool
    {
        $businessVerificationData = $verification->verificationData('submitted');

        $shareholders = $businessVerificationData['shareholders'];

        $shareHoldersFirstName = $requestData['shareholders_first_name'];
        $shareHoldersLastName = $requestData['shareholders_last_name'];
        $shareHoldersIdNumber = $requestData['shareholders_id_number'];
        $shareHoldersIsDirector = $requestData['shareholders_is_director'];
        $shareHoldersIsOwner = $requestData['shareholders_is_owner'];
        $shareHoldersIsExecutive = $requestData['shareholders_is_executive'];
        $shareHoldersDob = $requestData['shareholders_dob'];
        $shareHoldersAddress = $requestData['shareholders_address'];
        $shareHoldersPostal = $requestData['shareholders_postal'];
        $shareHoldersTitle = $requestData['shareholders_title'];
        $shareHoldersEmail = $requestData['shareholders_email'];

        $businessPersons = $verification->persons()->get();
        foreach ($businessPersons as $businessPerson) {
            $verification->persons()->detach($businessPerson->getKey());
            $businessPerson->delete();
        }

        foreach ($shareholders as $key => $shareholder) {
            $businessPerson = new \App\Business\Person();
            $businessPerson->business_id = $this->business->getKey();
            $businessPerson->first_name = $shareHoldersFirstName[$key];
            $businessPerson->last_name = $shareHoldersLastName[$key];
            $businessPerson->id_number = $shareHoldersIdNumber[$key];
            $businessPerson->dob = $shareHoldersDob[$key];
            $businessPerson->address = $shareHoldersAddress[$key];
            $businessPerson->postal_code = $shareHoldersPostal[$key];
            $businessPerson->title = $shareHoldersTitle[$key] ?? null;
            $businessPerson->email = $shareHoldersEmail[$key] ?? null;

            // reset
            $relationship = \App\Business\Person::DEFAULT_RELATIONSHIP;

            // we use 2 type because we have title on params below
            $paramRelationship = $relationship;

            $this->isRepresentativePerson(false);

            if (isset($shareHoldersIsDirector[$key]) && $shareHoldersIsDirector[$key] == 'yes') {
                $relationship['director'] = true;
                $paramRelationship['director'] = true;
            }

            if (isset($shareHoldersIsOwner[$key]) && $shareHoldersIsOwner[$key] == 'yes') {
                $relationship['owner'] = true;
                $paramRelationship['owner'] = true;
            }

            if (isset($shareHoldersIsExecutive[$key]) && $shareHoldersIsExecutive[$key] == 'yes') {
                $relationship['executive'] = true;
                $paramRelationship['executive'] = true;
            }

            $paramRelationship['title'] = $shareHoldersTitle[$key] ?? null;

            $businessPerson->relationship = $relationship;
            $businessPerson->save();

            $businessPerson->verifications()->attach($verification->getKey());
            $businessPerson->paymentProviders()->attach($this->businessPaymentProvider->getKey());

            ######## store data to stripe person api
            $this->createStripePerson($businessPerson, $paramRelationship);
        }

        $this->createRepresentativePersonFromOwner($verification, $requestData);

        return true;
    }

    /**
     * @param Verification $verification
     * @param array $requestData
     * @return void
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function createRepresentativePersonFromOwner(Verification $verification, array $requestData)
    {
        // create representative person from user owner
        $owner = $this->business->owner()->first();

        $businessPerson = new \App\Business\Person();
        $businessPerson->business_id = $this->business->getKey();
        $businessPerson->first_name = $owner->first_name;
        $businessPerson->last_name = $owner->last_name;
        $businessPerson->id_number = $requestData['nric'];
        $businessPerson->dob = $requestData['dob'];
        $businessPerson->address = $requestData['regadd'];
        $businessPerson->postal_code = '';
        $businessPerson->title = 'representative';
        $businessPerson->email = $requestData['email'];
        $businessPerson->phone = $this->business->phone_number ?? null;

        $relationship = \App\Business\Person::DEFAULT_RELATIONSHIP;

        $this->isRepresentativePerson(true);
        $relationship['representative'] = true;

        $businessPerson->relationship = $relationship;
        $businessPerson->save();

        $paramRelationship = $relationship;

        $paramRelationship['representative'] = true;
        $paramRelationship['title'] = $businessPerson->title;

        $businessPerson->verifications()->attach($verification->getKey());
        $businessPerson->paymentProviders()->attach($this->businessPaymentProvider->getKey());

        ######## store data to stripe person api
        $this->createStripePerson($businessPerson, $paramRelationship);
    }

    /***
     * @param \App\Business\Person $businessPerson
     * @param array $paramRelationship
     * @return \App\Business\Person
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function createStripePerson(
        \App\Business\Person $businessPerson,
        array $paramRelationship
    ): \App\Business\Person
    {
        $handler = Person\Create::new($this->business->payment_provider)
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

        $dobArr = $this->validateDateOfBirth($businessPerson->dob);

        $idNumber = $this->validateIdNumber($businessPerson->id_number);

        // for now follow business data
        $nationality = $this->business->country;

        $params = [
            'full_name_aliases' => [$businessPerson->first_name, $businessPerson->last_name],
            'first_name' => $businessPerson->first_name,
            'last_name' => $businessPerson->last_name,
            'email' => $businessPerson->email,
            'id_number' => $idNumber,
            'phone' => $businessPerson->phone ? $this->validatePhoneNumber($businessPerson->phone) : null,
            'address' => [
                'line1' => $this->validateAddress($businessPerson->address),
                'postal_code' => $businessPerson->postal_code,
            ],
            'dob' => $dobArr,
            'nationality' => $nationality,
            'metadata' => [
                'business_person_id' => $businessPerson->getKey(),
                'business_id' => $businessPerson->business_id,
                'platform' => Config::get('app.name'),
                'environment' => Config::get('app.env'),
            ],
            'relationship' => $paramRelationship
        ];

        return $handler->isRepresentative($this->isRepresentativePerson)
            ->handle($businessPerson, $params);
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

    /***
     * @return $this
     */
    public function setPaymentProvider() : self
    {
        $this->businessPaymentProvider = $this->business->paymentProviders()
            ->where('payment_provider', $this->business->payment_provider)
            ->first();

        return $this;
    }

    /**
     * Create Business Person for Individual type
     * @param PaymentProvider $paymentProvider
     * @param Verification $verification
     * @return \App\Business\Person
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    protected function createBusinessPerson(PaymentProvider $paymentProvider, Verification $verification) : \App\Business\Person
    {
        if ($paymentProvider->payment_provider != $this->business->payment_provider) {
            throw new \Exception("payment provider not same with active business");
        }

        if ($paymentProvider->payment_provider_account_type !== 'custom') {
            throw new \Exception("payment provider account type not custom");
        }

        $stripeAccountData = $paymentProvider->data;

        if ($stripeAccountData == "") {
            throw new \Exception("payment provider account with empty data of stripe");
        }

        if (!isset($stripeAccountData['account'])) {
            throw new \Exception("payment provider account with no account key and individual key data of stripe");
        }

        if (isset($stripeAccountData['account']['individual'])) {
            // sometime individual key have set, sometime not set yet.
            // if individual key set, we create businessPerson from this key

            $individual = $stripeAccountData['account']['individual'];

            $businessPerson = new \App\Business\Person();
            $businessPerson->business_id = $this->business->getKey();
            $businessPerson->first_name = isset($individual['first_name']) ? $individual['first_name'] : null;;
            $businessPerson->last_name = isset($individual['last_name']) ? $individual['last_name'] : null;
            $businessPerson->address = isset($individual['address']['line1']) ? $individual['address']['line1'] : null;
            $businessPerson->postal_code = isset($individual['address']['postal_code']) ? $individual['address']['postal_code'] : null;
            $businessPerson->title = isset($individual['title']) ? $individual['title'] : null;
            $businessPerson->email = isset($individual['email']) ? $individual['email'] : null;

            if (isset($individual['id_number_provided']) && $individual['id_number_provided'] === true) {
                // after update verification, id number missing if id_number_provided key true
                // TODO need to check doc for this or testing without automatic valid
                $businessPerson->id_number = null;
            } elseif (isset($individual['id_number'])) {
                $businessPerson->id_number = $individual['id_number'];
            } else {
                // not sure how come
                $businessPerson->id_number = 'not provided';
            }

            if (isset($individual['dob'])) {
                $dob = $individual['dob'];

                if ($dob['year'] != "" && $dob['month'] != "" && $dob['day'] != "") {

                    $fulldob = $dob['year'] . '-' . $dob['month'] . '-' . $dob['day'];

                    $businessPerson->dob = $fulldob;
                }
            }

            $relationshipArr = \App\Business\Person::DEFAULT_RELATIONSHIP;

            if (isset($individual['relationship'])) {
                $relationshipData = $individual['relationship'];

                if (isset($relationshipData['owner']) && $relationshipData['owner'] === true) {
                    $relationshipArr['owner'] = true;
                }

                if (isset($relationshipData['director']) && $relationshipData['director'] === true) {
                    $relationshipArr['director'] = true;
                }

                if (isset($relationshipData['executive']) && $relationshipData['executive'] === true) {
                    $relationshipArr['executive'] = true;
                }

                if (isset($relationshipData['title'])) {
                    $businessPerson->title = $relationshipData['title'];
                }
            }

            $businessPerson->relationship = $relationshipArr;

            if (isset($individual['id'])) {
                $businessPerson->stripe_person_id = $individual['id'];
            }

            $businessPerson->data = $individual;

            $businessPerson->save();

            $verification->persons()->attach($businessPerson->getKey());
            $paymentProvider->persons()->attach($businessPerson->getKey());
        } else {
            // if individual key not set,
            // we create person from verification submitted data

            $verificationData = $verification->submitted_data;

            $businessPerson = new \App\Business\Person();
            $businessPerson->business_id = $this->business->getKey();
            $businessPerson->first_name = $verificationData['name'] ?? null;
            $businessPerson->last_name = null;
            $businessPerson->address = $verificationData['regadd'] ?? null;
            $businessPerson->postal_code = null;
            $businessPerson->title = null;
            $businessPerson->email = $verificationData['email'] ?? null;

            $businessPerson->id_number = $verificationData['nric'] ?? null;

            $businessPerson->dob = $verificationData['dob'] ?? null;

            $relationshipArr = \App\Business\Person::DEFAULT_RELATIONSHIP;

            $relationshipArr['representative'] = true; // set opener only

            $businessPerson->relationship = $relationshipArr;

            $businessPerson->save();

            $verification->persons()->attach($businessPerson->getKey());
            $paymentProvider->persons()->attach($businessPerson->getKey());

            $this->isRepresentativePerson(true);

            $this->createStripePerson($businessPerson, $relationshipArr);
        }

        Facades\Log::info('finish business person ... ');

        return $businessPerson;
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
     * @param int $value
     * @return void
     * @throws \Exception
     */
    protected function updateStripeInit(int $value = 0) : void
    {
        if ($this->businessPaymentProvider === null) {
            $this->businessPaymentProvider = $this->business->paymentProviders()
                ->where('payment_provider', $this->business->payment_provider)
                ->where('payment_provider_account_type', 'custom')
                ->first();
        }

        // 0 => queue job on process
        // 1 => queue job finish
        if (!in_array($value, [0, 1])) {
            throw new \Exception("Failed set value of `stripe_init` with {$value}");
        }

        $this->businessPaymentProvider->stripe_init = $value;
        $this->businessPaymentProvider->save();
    }
}
