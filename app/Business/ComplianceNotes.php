<?php

namespace App\Business;

use App\Business;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceNotes extends Model implements OwnableContract
{
    use Ownable, UsesUuid, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_compliance_notes';

    protected $fillable = ['risk_level','compliance_notes','risk_expiry_date'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
