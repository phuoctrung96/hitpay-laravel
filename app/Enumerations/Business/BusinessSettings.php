<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class BusinessSettings extends Enumeration
{
    const POINT_OF_SALES_REMARK = 'point_of_sales_remark';

    /**
     * @return array
     */
    public static function getDefault(): array
    {
        return [
            [
                'key' => self::POINT_OF_SALES_REMARK,
                'value' => false,
                'description' => 'Point of sales remark',
            ],
        ];
    }
}
