<?php

namespace App\Actions\User\Register;

use App\Business\BusinessCategory;
use App\Enumerations\CountryCode;
use Illuminate\Support\Collection;

class BusinessForm extends Action
{
    /**
     * @return array
     */
    public function process(): array
    {
        $selectedCountry = $this->request->session()->get('country', CountryCode::SINGAPORE);

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
        $countries = Collection::make([
            [
                'id' => CountryCode::SINGAPORE,
                'name' => 'Singapore',
                'active' => false,
            ],
            [
                'id' => CountryCode::MALAYSIA,
                'name' => 'Malaysia',
                'active' => false,
            ]
        ]);

        return $countries->map(function($item) use ($selectedCountry) {
            if ($item['id'] === $selectedCountry) {
                $item['active'] = true;
            }
            return $item;
        });
    }
}
