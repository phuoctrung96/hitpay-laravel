<?php

namespace App;

use HitPay\Agent\LogHelpers;
use HitPay\User\Contracts\Ownable as OwnableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Token as Model;

class Token extends Model implements OwnableContract
{
    use LogHelpers;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_access_tokens';

    /**
     * The indicator to not log executor.
     *
     * @var bool
     */
    protected $logRequestWithExecutor = false;
// todo delete firebase token too
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
