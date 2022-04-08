<?php

namespace App\Business;

use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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
            $cognitoData = $this->cognitohq_data;

            return [
                'nric' => $cognitoData['user']['id_number']['value'],
                'name' => $cognitoData['user']['name']['first'] . ' ' .$cognitoData['user']['name']['last'],
                'sex' => null,
                'residentialstatus' => null,
                'nationality' => null,
                'dob' => $cognitoData['user']['date_of_birth'],
                'regadd' => collect(Arr::only($cognitoData['user']['address'], ['street', 'street2', 'city', 'subdivision', 'postal_code', 'country_code']))
                    ->map(function ($value, $key) {
                        return strtoupper($key) . ' : ' . ($value['value'] ?? $value['desc'] ?? $value);
                    })->implode("\n"),
                'email' => $cognitoData['user']['email'],
                'uen' => null,
                'entity_name' => null,
                'entity_type' => null,
                'entity_status' => null,
                'registration_date' => null,
                'primary_activity' => null,
                'address' => null,
                'shareholders' => [],
            ];
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
}
