<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CheckoutCustomisation extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_checkout_customisations';

    public $incrementing = false;

    public $timestamps = false;
}
