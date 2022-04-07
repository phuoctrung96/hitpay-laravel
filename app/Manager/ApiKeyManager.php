<?php

namespace App\Manager;

use App\Business\ApiKey;
use App\Business;
use App\Logics\Business\ApiKeyRepository;

class ApiKeyManager
{
    public static function createNew()
    {
        return new ApiKey();
    }

    public static function create(Business $business) : ApiKey
    {
        $apiKey             = self::createNew();

        $apiKey->api_key    = self::generateApiKey();
        $apiKey->salt       = self::getGeneratSalt();
        $apiKey->is_enabled = true;

        $apiKey             = ApiKeyRepository::store($business, $apiKey);

        return $apiKey;
    }

    public static function findByApiKey(string $apiKey) : ?ApiKey
    {
        return ApiKey::where('api_key', $apiKey)->first();
    }

    public static function changeStatus(ApiKey $apiKey) : ApiKey
    {
        if ($apiKey->is_enabled) {
            return self::markAsDisabled($apiKey);
        }

        return self::markAsEnabled($apiKey);
    }

    public static function markAsEnabled(ApiKey $apiKey) : ApiKey
    {
        $apiKey->is_enabled = true;

        ApiKeyRepository::update($apiKey);

        return $apiKey;
    }

    public static function markAsDisabled(ApiKey $apiKey) : ApiKey
    {
        $apiKey->is_enabled = false;
        
        ApiKeyRepository::update($apiKey);

        return $apiKey;
    }

    public static function delete(ApiKey $apiKey) : ?bool
    {
        return ApiKeyRepository::delete($apiKey);
    }

    private static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32)); 
    }

    public static function getGeneratSalt() 
    {
        $charset        = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randStringLen  = 64;   
        $randString     = "";

        for ($i = 0; $i < $randStringLen; $i++) {
            $randString .= $charset[mt_rand(0, strlen($charset) - 1)];
        }
   
        return $randString;
    }  
}