<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model implements OwnableContract
{
    use Ownable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_api_keys';

    public function getMaskedApiKeyAttribute()
    {
        return substr($this->api_key, 0, 4) . str_repeat("*", strlen($this->api_key)-8) . substr($this->api_key, -4);
    } 
}