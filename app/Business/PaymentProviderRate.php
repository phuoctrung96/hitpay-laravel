<?php

namespace App\Business;

use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProviderRate extends Model
{
    use UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_payment_provider_rates';

    // fixed amount - // null means use default
    // percentage - // null means use default
    // if starts at = null means not active

    protected $casts = [
        'fixed_amount' => 'int',
        'percentage' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function paymentProvider() : BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'business_payment_provider_id', 'id', 'payment_provider');
    }
}
