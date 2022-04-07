<?php

namespace App\Models\Business\Charge;

use App\Business;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoRefund extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * @inerhitdoc
     */
    protected $table = 'business_charge_auto_refunds';

    /**
     * @inerhitdoc
     */
    protected $casts = [
        'data' => 'array',
        'amount' => 'int',
        'refunded_at' => 'datetime',
    ];

    /**
     * @inerhitdoc
     */
    protected $guarded = [
        //
    ];

    /**
     * @inerhitdoc
     */
    public function business() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id', __FUNCTION__);
    }

    /**
     * Get the related charge.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function charge() : BelongsTo
    {
        return $this->belongsTo(Business\Charge::class, 'business_charge_id', 'id', __FUNCTION__);
    }

    /**
     * Get the related payment intent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentIntent() : BelongsTo
    {
        return $this->belongsTo(Business\PaymentIntent::class, 'business_payment_intent_id', 'id', __FUNCTION__);
    }
}
