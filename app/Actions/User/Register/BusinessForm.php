<?php

namespace App\Actions\User\Register;

use App\Actions\User\UserInfoByIp;
use App\Business\BusinessCategory;
use App\Enumerations\CountryCode;
use Illuminate\Support\Collection;

class BusinessForm extends Action
{
    use UserInfoByIp;

    /**
     * @return array
     */
    public function process(): array
    {
        $selectedCountry = $this->getUserInformationByIp('countrycode') ?? CountryCode::SINGAPORE;

        return [
            'countries' => $this->getCountries($selectedCountry),
            'business_categories' => BusinessCategory::all(),
            'selected_country' => $selectedCountry,
        ];
    }

    /**
     * @param string $selectedCountry
     * @return Collection
     */
    private function getCountries(string $selectedCountry): Collection
    {
        $countries = $this->getDefaultCountries();

        return $countries->map(function($item) use ($selectedCountry) {
            if ($item['id'] === $selectedCountry) {
                $item['active'] = true;
            }
            return $item;
        });
    }
}
