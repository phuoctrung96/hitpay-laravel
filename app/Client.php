<?php

namespace App;

use HitPay\Business\Contracts\Ownable as OwnableBusinessContract;
use HitPay\Business\Ownable;
use HitPay\User\Contracts\Ownable as OwnableContract;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Client as Model;

class Client extends Model implements OwnableContract, OwnableBusinessContract
{
    use UsesUuid;
    use Ownable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_clients';

    /**
     * Get the owner of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\User
     */
    public function owner() : BelongsTo
    {
        return $this->user();
    }
}
