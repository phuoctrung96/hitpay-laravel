<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\ApiKey;
use Illuminate\Support\Facades\DB;

class ApiKeyRepository
{
    public static function store(Business $business, ApiKey $apiKey) : ApiKey
    {
        return DB::transaction(function () use ($business, $apiKey) : ApiKey {
            $business->apikeys()->save($apiKey);
    
            $apiKey = $apiKey->refresh();
    
            return $apiKey;
        }, 3);
    }
    
    public static function update(ApiKey $apiKey) : ApiKey
    {
        $chargeModel = DB::transaction(function () use ($apiKey) : ApiKey {
            $apiKey->save();

            return $apiKey;
        }, 3);

        return $apiKey;
    }

    public static function delete(ApiKey $apiKey) : ?bool
    {
        return DB::transaction(function () use ($apiKey) : ?bool {
            return $apiKey->delete();
        }, 3);
    }
}
