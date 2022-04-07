<?php

namespace App\Business;

use App\Logics\ConfigurationRepository;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;

class Verification extends Model
{
    use UsesUuid, SoftDeletes, Ownable;

    protected $table = 'business_verifications';

    protected $casts = [
        'submitted_data' => 'array',
        'my_info_data' => 'array',
        'cognitohq_data' => 'array',
        'verified_at' => 'datetime',
    ];

    protected $guarded = [];

    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    public function markAsVerified(array $data): void
    {
        if ($this->isVerified()) {
            return;
        }

        $this->update([
            'submitted_data' => $data,
            'verified_at' => $this->freshTimestamp(),
        ]);
    }

    public function getPersonsForStripe() : array
    {
        $type = $this->verification_provider;

        if ($type === 'myinfo') {
            return $this->getMyInfoPersonsForStripe();
        } elseif ($type === 'cognito') {
            return $this->getCognitoPersonsForStripe();
        }

        return [];
    }

    protected function getMyInfoPersonsForStripe() : array
    {
        if ($this->verification_provider !== 'myinfo') {
            return [];
        }

        if ($this->type === 'business') {
            $persons = $this->my_info_data['data']['entity']['shareholders']['shareholders-list'] ?? [];

            $personCollection = [];

            foreach ($persons as $person) {
                if (( $person['category']['code'] ?? '0' ) !== '1') {
                    continue;
                }

                if (!isset($person['person-reference']['person-name']['value'])) {
                    continue;
                }

                if (!is_string($person['person-reference']['person-name']['value'])) {
                    continue;
                }

                $person = $person['person-reference'];

                $personCollection[] = [
                    'first_name' => Str::title($person['person-name']['value']),
                    'id_number' => $person['idno']['value'] ?? null,
                    'nationality' => $person['nationality']['code'] ?? 'XX',
                    'metadata' => $this->getStripePersonMetadata(),
                ];
            }

            return $personCollection;
        }

        $person = $this->my_info_data['data'];

        if (!isset($person['name']['value']) || !is_string($person['name']['value'])) {
            return [];
        }

        $personData = [
            'first_name' => Str::title($person['name']['value']),
            'id_number' => $person['uinfin']['value'] ?? null,
            'nationality' => $person['nationality']['code'] ?? 'XX',
            'metadata' => $this->getStripePersonMetadata(),
        ];

        if (isset($person['sex']['code'])) {
            if ($person['sex']['code'] === 'M') {
                $personData['gender'] = 'male';
            } elseif ($person['sex']['code'] === 'F') {
                $personData['gender'] = 'female';
            }
        }

        if (isset($person['dob']['value']) && preg_match('/^\d{4}-[0-1][0-9]-[0-3][0-9]$/', $person['dob']['value'])) {
            $dob = explode('-', $person['dob']['value']);

            $personData['dob'] = [
                'day' => $dob[2],
                'month' => $dob[1],
                'year' => $dob[0],
            ];
        }

        // address

        if (isset($person['regadd']['type']) && $person['regadd']['type'] === 'SG') {
            $address = $person['regadd'];

            $line1Details = Collection::make([
                'block' => $address['block']['value'] ?? null,
                'building' => $address['building']['value'] ?? null,
                'street' => $address['street']['value'] ?? null,
                'floor' => $address['floor']['value'] ?? null,
                'unit' => $address['unit']['value'] ?? null,
            ])->map(function ($value) {
                return is_null($value) ? null : trim($value);
            })->filter(function ($value) {
                return is_string($value) && Str::length($value) > 0;
            })->toArray();

            $line1 = '';

            if (isset($line1Details['block'])) {
                $line1 .= $line1Details['block'];

                if (isset($line1Details['building'])) {
                    $line1 .= ", {$line1Details['building']}";
                }
            }

            if (isset($line1Details['street'])) {
                $line1Details['street'] = Str::title($line1Details['street']);

                $line1 .= " {$line1Details['street']}";
            }

            $line1 .= ' #';

            if (isset($line1Details['floor'])) {
                $line1 .= $line1Details['floor'];

                if (isset($line1Details['unit'])) {
                    $line1 .= "-{$line1Details['unit']}";
                }
            }

            $personData['address'] = [
                'line1' => $line1,
                'postal_code' => $address['postal']['value'] ?? null,
                'city' => $address['country']['desc'] ?? null,
                'country' => $address['country']['code'] ?? null,
            ];
        }

        return [ $personData ];
    }

    protected function getCognitoPersonsForStripe() : array
    {
        $user = $this->cognitohq_data['user'] ?? [];

        if (!isset($user['name']['first'], $user['name']['last'])) {
            return [];
        }

        $personData = [
            'first_name' => $user['name']['first'],
            'last_name' => $user['name']['last'],
            'id_number' => $user['id_number']['value'] ?? null,
            'metadata' => $this->getStripePersonMetadata(),
        ];

        if (isset($user['date_of_birth']) && preg_match('/^\d{4}-[0-1][0-9]-[0-3][0-9]$/', $user['date_of_birth'])) {
            $dob = explode('-', $user['date_of_birth']);

            $personData['dob'] = [
                'day' => $dob[2],
                'month' => $dob[1],
                'year' => $dob[0],
            ];
        }

        if (isset($user['address']['country_code']) && is_string($user['address']['country_code'])) {
            $personData['address'] = [
                'line1' => $user['address']['street'] ?? null,
                'line2' => $user['address']['street2'] ?? null,
                'postal_code' => $user['address']['postal_code'] ?? null,
                'city' => $user['address']['city'] ?? null,
                'state' => $user['address']['subdivision'] ?? null,
                'country' => $user['address']['country_code'] ?? null,
            ];
        }

        return [ $personData ];
    }

    protected function getStripePersonMetadata() : array
    {
        return [
            'business_id' => $this->business->getKey(),
            'platform' => Facades\Config::get('app.name'),
            'version' => ConfigurationRepository::get('platform_version'),
            'environment' => Facades\Config::get('app.env'),

        ];
    }

    public function verificationData($dataType = 'myinfo')
    {
        if ($dataType == 'submitted') {
            return $this->submitted_data;
        } elseif ($dataType == 'myinfo') {
            $personData = $this->type === 'business' ? $this->my_info_data['data']['person'] : $this->my_info_data['data'];
            $entity_address = $this->my_info_data['data']['entity']['addresses']['addresses-list'][0] ?? [];

            $verification_data = [
                'nric' => $personData['uinfin']['value'],
                'name' => $personData['name']['value'],
                'sex' => $personData['sex']['value'] ?? $personData['sex']['desc'],
                'residentialstatus' => $personData['residentialstatus']['value'] ?? $personData['residentialstatus']['desc'],
                'nationality' => $personData['nationality']['value'] ?? $personData['nationality']['desc'],
                'dob' => $personData['dob']['value'],
                'regadd' => $address = collect(Arr::only($personData['regadd'], ['unit', 'block', 'floor', 'postal', 'street', 'country']))
                    ->map(function ($value, $key) {
                        return strtoupper($key) . ' : ' . ($value['value'] ?? $value['desc'] ?? $value);
                    })->implode("\n"),
                'email' => $personData['email']['value'],
                'uen' => $this->my_info_data['uen'] ?? null,
                'entity_name' => $this->my_info_data['data']['entity']['basic-profile']['entity-name']['value'] ?? null,
                'entity_type' => $this->my_info_data['data']['entity']['basic-profile']['entity-type']['desc'] ?? null,
                'entity_status' => $this->my_info_data['data']['entity']['basic-profile']['entity-status']['value'] ?? null,
                'registration_date' => $this->my_info_data['data']['entity']['basic-profile']['registration-date']['value'] ?? null,
                'primary_activity' => $this->my_info_data['data']['entity']['basic-profile']['primary-activity']['desc'] ?? null,
                'address' => collect(Arr::only($entity_address, ['unit', 'block', 'floor', 'postal', 'street', 'country'
                ]))->map(function ($value, $key) {
                    return strtoupper($key) . ' : ' . ($value['value'] ?? $value['desc']);
                })->implode("\n"),
            ];
            $verification_data['shareholders'] = [];
            foreach ($shareholders = $this->my_info_data['data']['entity']['shareholders']['shareholders-list'] ?? [] as $key => $shareholder) {
                if (!isset($shareholder['person-reference']['person-name']['value']) && !isset($shareholder['entity-reference']['entity-name']['value']))
                    continue;
                array_push($verification_data['shareholders'], $shareholder['person-reference']['person-name']['value'] ?? $shareholder['entity-reference']['entity-name']['value']);
            }

            return $verification_data;
        } elseif ($dataType == 'cognito') {
//            $cognitoData = $this->cognitohq_data;

//            if ($cognitoData) {
//                return [
//                    'nric' => $cognitoData['user']['id_number']['value'],
//                    'name' => $cognitoData['user']['name']['first'] . ' ' .$cognitoData['user']['name']['last'],
//                    'sex' => null,
//                    'residentialstatus' => null,
//                    'nationality' => null,
//                    'dob' => $cognitoData['user']['date_of_birth'],
//                    'regadd' => collect(Arr::only($cognitoData['user']['address'], ['street', 'street2', 'city', 'subdivision', 'postal_code', 'country_code']))
//                        ->map(function ($value, $key) {
//                            return strtoupper($key) . ' : ' . ($value['value'] ?? $value['desc'] ?? $value);
//                        })->implode("\n"),
//                    'email' => $cognitoData['user']['email'],
//                    'uen' => null,
//                    'entity_name' => null,
//                    'entity_type' => null,
//                    'entity_status' => null,
//                    'registration_date' => null,
//                    'primary_activity' => null,
//                    'address' => null,
//                    'shareholders' => [],
//                ];
//            } else {
                return [
                    'nric' => null,
                    'name' => null,
                    'sex' => null,
                    'residentialstatus' => null,
                    'nationality' => null,
                    'dob' => null,
                    'regadd' => null,
                    'email' => null,
                    'uen' => null,
                    'entity_name' => null,
                    'entity_type' => null,
                    'entity_status' => null,
                    'registration_date' => null,
                    'primary_activity' => null,
                    'address' => null,
                    'shareholders' => [],
                ];
//            }
        } else {
            throw new \Exception("Datatype to get verification data must be set");
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function persons() : MorphToMany
    {
        return $this->morphToMany(
            Person::class,
            'associable',
            'business_associable_persons',
            'associable_id',
            'person_id',
            'id',
            'id'
        );
    }

    /**
     * @return string
     */
    public function getStatusName() : string
    {
        if ($this->isVerified()) {
            return 'verified';
        }

        if (is_array($this->submitted_data) && count($this->submitted_data) > 0) {
            return 'submitted';
        }

        return 'pending';
    }
}
