<?php

namespace App;

use HitPay\Model\UsesUuid;
use Laravel\Passport\PersonalAccessClient as Model;

class PersonalAccessClient extends Model
{
    use UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_personal_access_clients';
}
