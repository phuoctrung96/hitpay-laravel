<?php

namespace App;

use Laravel\Passport\AuthCode as Model;

class AuthenticationCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_auth_codes';
}
