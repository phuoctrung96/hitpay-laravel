<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class PromotionAppliesToType extends Enumeration
{
    const ALL_PRODUCT = 1;

    const SPECIFIC_CATEGORIES = 2;

    const SPECIFIC_PRODUCTS = 3;

    /**
     * @param int $appliesType
     * @return string
     */
    public static function displayName(int $appliesType): string
    {
        $names = [
            self::ALL_PRODUCT  => 'Applies to all product',
            self::SPECIFIC_CATEGORIES => 'Applies to specific categories',
            self::SPECIFIC_PRODUCTS => 'Applies to specific products',
        ];

        return $names[$appliesType];
    }
}
