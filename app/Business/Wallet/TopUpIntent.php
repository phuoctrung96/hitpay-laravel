<?php

namespace App\Business\Wallet;

use App\Business\Wallet;
use HitPay\Agent\LogHelpers;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopUpIntent extends Model
{
    use LogHelpers, Ownable, UsesUuid;

    protected $table = 'business_wallet_top_up_intents';

    protected $casts = [
        'data' => 'array',
        'amount' => 'int',
        'expires_at' => 'datetime',
    ];

    protected $guarded = [
        //
    ];

    public function wallet() : BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'business_wallet_id', 'id', __FUNCTION__);
    }
}
