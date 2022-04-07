<?php

namespace App\Business;

use HitPay\Agent\LogHelpers;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundIntent extends Model
{
    use LogHelpers, Ownable, UsesUuid;

    protected $table = 'business_refund_intents';

    protected $casts = [
        'data' => 'array',
        'amount' => 'int',
        'expires_at' => 'datetime',
    ];

    protected $guarded = [
        //
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charge() : BelongsTo
    {
        return $this->belongsTo(Charge::class, 'business_charge_id', 'id', 'charge');
    }
}
