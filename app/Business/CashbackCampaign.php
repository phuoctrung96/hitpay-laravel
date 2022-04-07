<?php

namespace App\Business;

use App\Business;
use App\Business\CashbackCampaignRules;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashbackCampaign extends Model implements OwnableContract
{
    use Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_cashback_campaigns';

    protected $fillable = ['name','campaign_business_id','fund', 'status', 'payment_method', 'payment_sender'];

    public function campaignBusiness() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'campaign_business_id', 'id');
    }

    public function rules() : HasMany
    {
        return $this->hasMany(CashbackCampaignRules::class, 'campaign_id', 'id');
    }
}
