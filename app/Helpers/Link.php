<?php

namespace App\Helpers;

/**
 * Class Link
 * @package app\Helpers
 */
class Link
{
    /**
     * @param $link
     * @param $referer
     * @return string
     */
    public static function getCanceledLink($link, $referer)
    {
        if (strpos($link, '?') === false) {
            $link .= '?';
        } else {
            $link .= '&';
        }

        return $link . http_build_query(
                    [
                        'reference' => $referer,
                        'status' => 'canceled'
                    ]
                );
    }
}
