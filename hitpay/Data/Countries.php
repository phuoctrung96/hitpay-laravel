<?php

namespace HitPay\Data;

use App\Enumerations\CountryCode;
use Exception;
use HitPay\Data\Countries\Country;

class Countries
{
    public static function get(string $countryCode) : ?Country
    {
        if (!in_array($countryCode, static::all())) {
            throw new Exception("The selected country '{$countryCode}' is not available for the moment.");
        }

        $class = __CLASS__.'\\'.strtoupper($countryCode);

        return new $class;
    }

    public static function all() : array
    {
        return array_values(CountryCode::listConstants());
    }
}
