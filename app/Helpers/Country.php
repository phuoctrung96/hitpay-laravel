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
}
