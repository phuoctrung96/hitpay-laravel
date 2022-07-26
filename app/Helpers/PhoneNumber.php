<?php

namespace App\Helpers;

use HitPay\Data\Countries;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class PhoneNumber
{
    /**
     * @throws \Exception
     */
    public static function isValidLocalPhoneNumber(string $countryCode, string $phoneNumber): bool
    {
        $country = Countries::get($countryCode);

        $isPhoneNumberLocalValid = true;

        if ($country::USE_LOCAL_PHONE_NUMBER_ONLY) {
            $phoneUtil = PhoneNumberUtil::getInstance();

            $countryItem = $country->getRawData();

            try {
                $parsed = $phoneUtil->parse($phoneNumber, strtoupper($countryItem['id']));

                if ($parsed) {
                    $isPhoneNumberLocalValid = $phoneUtil->isValidNumberForRegion($parsed, strtoupper($countryItem['id']));
                }
            } catch (NumberParseException $e) {
                $isPhoneNumberLocalValid = false;
            }
        }

        return $isPhoneNumberLocalValid;
    }
}
