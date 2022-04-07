<?php

namespace App;

use Laravel\Passport\RefreshToken as Model;

class RefreshToken extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_refresh_tokens';
}
