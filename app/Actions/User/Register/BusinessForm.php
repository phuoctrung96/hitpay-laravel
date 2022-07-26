<?php

namespace App\Actions\User\Register;

use App\Actions\User\UserInfoByIp;
use App\Business\BusinessCategory;
use App\Enumerations\CountryCode;
use App\Logics\ConfigurationRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BusinessForm extends Action
{
    use UserInfoByIp;

    /**
     * @return array
     */
    public function process(): array
    {
        $selectedCountry = strtolower($this->getUserInformationByIp('countrycode') ?? CountryCode::SINGAPORE);

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
        $countries = $this->getDefaultCountries()->map(function ($item) use ($selectedCountry) {
            $item['active'] = $item['id'] === $selectedCountry;

            return $item;
        });

        $countriesAvailable = ConfigurationRepository::get('countries_available', []);

        return $countries->whereIn('id', array_merge($countriesAvailable, [
            CountryCode::MALAYSIA,
            CountryCode::SINGAPORE,
        ]));
    }
}
