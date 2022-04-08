<?php

namespace App\Business;

use App\Events\GatewayProvider\Saved;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use Illuminate\Database\Eloquent\Model;

class GatewayProvider extends Model implements OwnableContract
{
    use Ownable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_gateway_providers';

    protected $casts = [
        'methods' => 'array'
    ];

    public function getArrayMethodsAttribute()
    {
        if (empty($this->methods)) {
            return [];
        }

        return is_array($this->methods) ? $this->methods : json_decode($this->methods, true);
    }
}
