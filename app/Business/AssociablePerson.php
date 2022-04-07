<?php

namespace App\Business;

use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssociablePerson extends Model
{
    use Ownable, UsesUuid, SoftDeletes;

    protected $table = 'business_associable_persons';

    protected $guarded = [];
}
