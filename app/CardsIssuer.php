<?php

namespace App;

use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class CardsIssuer extends Model
{
    use UsesUuid;

    protected $table = 'cards_issuers';

    protected $guarded = [];
}
