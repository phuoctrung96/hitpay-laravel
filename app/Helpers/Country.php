<?php

namespace App\Helpers;

use App\Enumerations\AllCountryCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * Class Country
 * @package App\Helpers
 */
class Country
{
    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function getCountries(): array
    {
        $countries = new Collection();

        foreach (AllCountryCode::listConstants() as $value) {
            if (Lang::has('misc.country.'.$value)) {
                $name = Lang::get('misc.country.'.$value);
            } else {
                $name = $value;
            }

            $countries->add([
                'code' => $value,
                'name' => $name,
            ]);
        }

        if ($countries->count() == 0) {
            return [];
        }

        return $countries->sortBy('name')->values()->toArray();
    }

    /**
     * @param string $selectedCountryCode
     * @return Collection
     * @throws \ReflectionException
     */
    public static function getCountriesSelected(string $selectedCountryCode): Collection
    {
        $countries = collect(static::getCountries());

        return $countries->map(function($item) use ($selectedCountryCode) {
            if ($item['code'] === $selectedCountryCode) {
                $item['active'] = true;
            } else {
                $item['active'] = false;
            }

            return $item;
        });
    }
}
