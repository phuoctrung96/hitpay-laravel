<?php

namespace App\Business;

use App\Business;
use App\StripeTerminal;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripeTerminalLocation extends Model
{
    use UsesUuid;

    protected $table = 'business_stripe_terminal_locations';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function terminals() : HasMany
    {
        return $this->hasMany(StripeTerminal::class, 'business_stripe_terminal_location_id', 'id');
    }

    public function business() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id', 'business');
    }
}
