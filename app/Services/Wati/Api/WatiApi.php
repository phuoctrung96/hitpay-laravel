<?php

namespace App\Services\Wati\Api;

use App\Business;

abstract class WatiApi
{
    protected $business;

    protected $baseUrl;

    protected $accessToken;

    public function __construct(Business $business)
    {
        $this->business = $business;

        $this->baseUrl = env('WATI_SERVER_URL');

        $this->accessToken = env('WATI_ACCESS_TOKEN');
    }
}
