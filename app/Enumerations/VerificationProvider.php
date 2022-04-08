<?php

namespace App\Enumerations;

class VerificationProvider extends Enumeration
{
    const COGNITO = 'cognito';
    const MYINFO = 'myinfo';

    /**
     * @param $provider
     * @return string
     */
    public static function displayName($provider) : string
    {
        $names = [
            self::COGNITO  => 'Cognito',
            self::MYINFO  => 'MyInfo',
        ];

        return $names[$provider];
    }
}
