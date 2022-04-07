<?php

namespace App\Business;

use App\Business;
use Carbon\Carbon;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon starts_at
 * @property Carbon ends_at
 * @property Business business
 * @property float referral_fee
 */
class BusinessReferral extends Model
{
    use UsesUuid;

    protected $guarded = [];

    public $dates = ['starts_at', 'ends_at'];

    protected $with = ['business'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function findByCode(string $code): ?self
    {
        return self::query()->where('code', $code)->first();
    }

    public function referredBusinesses():HasMany
    {
        return $this->hasMany(Business::class, 'referred_by_id');
    }
}
