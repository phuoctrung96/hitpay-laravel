<?php

namespace App\Actions\Business\Stripe\VerificationOnboard;

use App\Business;
use HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException;
use HitPay\Stripe\CustomAccount\Exceptions\GeneralException;
use HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException;
use HitPay\Stripe\CustomAccount;
use Illuminate\Support\Facades;
use HitPay\Stripe\CustomAccount\File;
use Illuminate\Support\Facades\Config;

class UpdateIndividualPerson extends Action
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
        $person = json_decode($this->data['person'], true);

        $businessPaymentProvider = $this->business->paymentProviders()
            ->where('id', $this->data['businessPaymentProviderId'])
            ->first();

        if ($businessPaymentProvider === null) {
            throw new \Exception('Payment provider not found from business ' . $this->business->getKey());
        }

        $params = [
            'person_full_name_aliases' => $person['full_name_aliases'] ?? '',
            'person_first_name' => $person['first_name'] ?? '',
            'person_last_name' => $person['last_name'] ?? '',
            'person_title' => $person['title'] ?? '',
            'person_email' => $person['email'] ?? '',
            'person_dob_day' => $person['dob_day'] ?? '',
            'person_dob_month' => $person['dob_month'] ?? '',
            'person_dob_year' => $person['dob_year'] ?? '',
            'person_address_country' => $person['address_country'] ?? '',
            'person_address_city' => $person['address_city'] ?? '',
            'person_address_state' => $person['address_state'] ?? '',
            'person_address_line1' => $person['address_line1'] ?? '',
            'person_address_line2' => $person['address_line2'] ?? '',
            'person_address_postal_code' => $person['address_postal_code'] ?? '',
            'person_phone' => $person['phone'] ?? '',
            'person_percent_ownership' => (float)($person['percent_ownership'] != '') ?? '0.00'
        ];

        // validate 13 year old for dob
        $time = strtotime("-13 year", time());
        $yearMaxLimit = date("Y", $time);

        $rules = [
            'person_full_name_aliases' => 'required|string',
            'person_first_name' => 'required|string',
            'person_last_name' => 'nullable|string',
            'person_email' => 'required|email',
            'person_dob_day' => 'required|numeric|min:1|max:31',
            'person_dob_month' => 'required|numeric|min:1|max:12',
            'person_dob_year' => 'required|numeric|digits:4|max:'.$yearMaxLimit,
            'person_address_country' => 'required|string',
            'person_address_city' => 'nullable|string',
            'person_address_state' => 'nullable|string',
            'person_address_line1' => 'required|string',
            'person_address_line2' => 'nullable|string',
            'person_address_postal_code' => 'required|string',
            'person_phone' => 'required|string',
            'person_percent_ownership' => 'nullable|numeric|between:0,99.99',
        ];

        Facades\Validator::make($params, $rules)->validate();

        $businessPersons = $businessPaymentProvider->persons()->get();

        $foundPerson = false;
        $activePerson = null;

        foreach ($businessPersons as $businessPerson) {
            if ($businessPerson->stripe_person_id == $person['id']) {
                $foundPerson = true;
                $activePerson = $businessPerson;
                break;
            }
        }

        if (!$foundPerson) {
            throw new \Exception('Person not found from business ' . $this->business->getKey());
        }

        Facades\DB::beginTransaction();

        try {
            $this->uploadDocumentFile($businessPaymentProvider, $activePerson);

            $activePerson = $this->updateBusinessPerson($activePerson, $params);

            $this->updateStripePerson($activePerson);

            $businessPaymentProvider = $this->updateAccount();

            Facades\DB::commit();

            return $businessPaymentProvider;
        } catch (\Exception $exception) {
            Facades\DB::rollback();

            throw $exception;
        }
    }

    /**
     * @param Business\Person $activePerson
     * @param array $params
     * @return Business\Person
     */
    private function updateBusinessPerson(Business\Person $activePerson, array $params) : Business\Person
    {
        $activePerson->alias_name = $params['person_full_name_aliases'];
        $activePerson->first_name = $params['person_first_name'];
        $activePerson->last_name = $params['person_last_name'];
        $activePerson->email = $params['person_email'];
        $dob = $params['person_dob_year'] . '-' . $params['person_dob_month'] . '-' . $params['person_dob_day'];
        $activePerson->dob = $dob;
        $activePerson->country = strtoupper($params['person_address_country']);
        $activePerson->state = $params['person_address_state'];
        $activePerson->city = $params['person_address_city'];
        $activePerson->address = $params['person_address_line1'];
        $activePerson->address2 = $params['person_address_line2'];
        $activePerson->postal_code = $params['person_address_postal_code'];
        $activePerson->phone = $params['person_phone'];
        $activePerson->percent_ownership = $params['person_percent_ownership'];
        $activePerson->save();

        return $activePerson;
    }

    /***
     * @param Business\Person $businessPerson
     * @return Business\Person
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function updateStripePerson(Business\Person $businessPerson) : Business\Person
    {
        $handler = CustomAccount\Person\Update::new($this->business->payment_provider)
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

        $phoneNumber = $this->validatePhoneNumber($businessPerson->phone);

        $params = [
            'full_name_aliases' => [$businessPerson->alias_name],
            'first_name' => $businessPerson->first_name,
            'last_name' => $businessPerson->last_name,
            'email' => $businessPerson->email,
            'address' => [
                'line1' => $this->validateAddress($businessPerson->address),
                'postal_code' => $businessPerson->postal_code,
                'country' => $businessPerson->country,
                'city' => $businessPerson->city,
                'state' => $businessPerson->state,
            ],
            'relationship' => [
                'title' => $businessPerson->title,
                'percent_ownership' => $businessPerson->percent_ownership != '0.00' ? $businessPerson->percent_ownership : '',
            ],
            'nationality' => $businessPerson->country,
            'dob' => $dobArr,
            'phone' => $phoneNumber,
            'metadata' => [
                'business_person_id' => $businessPerson->getKey(),
                'business_id' => $businessPerson->business_id,
                'platform' => Config::get('app.name'),
                'environment' => Config::get('app.env'),
            ]
        ];

        foreach ($businessPerson->relationship as $key => $relationship) {
            if ($key == 'owner' && $relationship === true) {
                $params['relationship']['owner'] = true;
            }

            if ($key == 'director' && $relationship === true) {
                $params['relationship']['director'] = true;
            }

            if ($key == 'representative' && $relationship === true) {
                $params['relationship']['representative'] = true;
                $params['relationship']['executive'] = true;
            }
        }

        if (in_array($businessPerson->data['verification']['status'], ['unverified', 'pending'])) {
            // sometime got error when set verification
            // You cannot change `verification[document][back]` via API if an account is verified.
            // Please contact us via https://support.stripe.com/contact
            // if you need to change the legal entity information
            // associated with this account.
            $stripePerson = $this->getPerson($businessPerson);

            if (in_array($stripePerson->toArray()['verification']['status'], ['unverified', 'pending'])) {
                $businessFile = $businessPerson->files()->latest()->first();

                if ($businessFile) {
                    $params['verification'] = [
                        'document' => [
                            'back' => $businessFile->stripe_file_id
                        ]
                    ];
                }
            }
        }

        return $handler->handle($businessPerson, $params);
    }

    /**
     * @param Business\PaymentProvider $businessPaymentProvider
     * @param Business\Person $person
     * @return void
     * @throws AccountNotFoundException
     * @throws GeneralException
     * @throws InvalidStateException
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function uploadDocumentFile(
        Business\PaymentProvider $businessPaymentProvider,
        Business\Person $person
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
            $businessFile->persons()->attach($person->getKey());

            // submit to stripe file
            $responseHandler = $handler->setPurpose('identity_document')
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
