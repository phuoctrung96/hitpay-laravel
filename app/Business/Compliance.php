<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compliance extends Model implements OwnableContract
{
    use Ownable, UsesUuid, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_compliance';

    protected $fillable = ['entity_id','type','data'];

}
