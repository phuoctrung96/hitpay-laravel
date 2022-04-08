<?php

namespace App\Actions\User\Register;

use App\Actions\User\UserInfoByIp;
use Illuminate\Support\Collection;

class RegisterForm extends Action
{
    use UserInfoByIp;

    /**
     * @return array
     */
    public function process(): array
    {
        return [
            'countries' => $this->getCountries()
        ];
    }

    /**
     * @return Collection
     */
    private function getCountries(): Collection
    {
        $countryCode = $this->getUserInformationByIp('countrycode');

        if ($countryCode) {
            $availableCountryCodes = ['sg', 'my'];

            if (in_array($countryCode, $availableCountryCodes)) {
                return Collection::make([
                    [
                        'id' => 'sg',
                        'name' => 'Singapore',
                        'active' => $countryCode == 'sg',
                    ],
                    [
                        'id' => 'my',
                        'name' => 'Malaysia',
                        'active' => $countryCode == 'my',
                    ]
                ]);
            }
            else {
                return $this->getDefaultCountries();
            }
        }
        else {
            return $this->getDefaultCountries();
        }
    }

    private function getDefaultCountries()
    {
        return Collection::make([
            [
                'id' => 'sg',
                'name' => 'Singapore',
                'active' => true,
            ],
            [
                'id' => 'my',
                'name' => 'Malaysia',
                'active' => false,
            ]
        ]);
    }
}
