<?php

namespace App\Helpers;

class Pagination
{
    const DEFAULT_PER_PAGE = 5;
    const AVAILABLE_PAGE_NUMBER = [
        5,
        10,
        25,
        50,
        100,
        200,
    ];

    public static function getDefaultPerPage()
    {
        return self::DEFAULT_PER_PAGE;
    }
}
