<?php

namespace App;

use App\Business\StripeTerminalLocation;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeTerminal extends Model
{
    use UsesUuid;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function location() : BelongsTo
    {
        return $this->belongsTo(StripeTerminalLocation::class,
            'business_stripe_terminal_location_id', 'id', 'location');
    }
}
