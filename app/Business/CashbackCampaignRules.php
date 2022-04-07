<?php

namespace App\Business;

use App\Business;
use App\Business\CashbackCampaign;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackCampaignRules extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_cashback_campaigns_rules';

    protected $fillable = ['business_id','currency','min_spend', 'maximum_cap', 'cashback_amt_fixed', 'cashback_amt_percent', 'total_cashback','balance_cashback'];

    public function campaign() : BelongsTo
    {
        return $this->belongsTo(CashbackCampaign::class, 'campaign_id', 'id');
    }
}
