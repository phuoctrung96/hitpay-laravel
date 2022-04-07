<?php

namespace App\Actions\User\Register;

use App\Actions\User\UserInfoByIp;
use App\Enumerations\CountryCode;
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
            $availableCountryCodes = [CountryCode::SINGAPORE, CountryCode::MALAYSIA];

            if (in_array($countryCode, $availableCountryCodes)) {
                return Collection::make([
                    [
                        'id' => CountryCode::SINGAPORE,
                        'name' => 'Singapore',
                        'active' => $countryCode == CountryCode::SINGAPORE,
                    ],
                    [
                        'id' => CountryCode::MALAYSIA,
                        'name' => 'Malaysia',
                        'active' => $countryCode == CountryCode::MALAYSIA,
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
}
