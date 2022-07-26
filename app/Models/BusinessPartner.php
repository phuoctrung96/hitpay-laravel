<?php

namespace App\Models;

use App\Business;
use App\Enumerations\BusinessPartnerStatus;
use App\PartnerCommission;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed business_id
 * @property Business[] businesses
 * @property float commission
 * @property User user
 * @property Business business
 * @property \Illuminate\Support\Carbon|mixed last_commission_done_at
 */
class BusinessPartner extends Model
{
    protected $guarded = [];

    protected $dates = ['last_commission_done_at'];

    protected $appends = ['referral_url'];

    protected $casts = [
        'services' => 'array',
        'platforms' => 'array',
        'pricing' => 'array',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // auto-sets values on creation
        static::creating(function (self $model) : void {
            $model->commission = $model->commission ?? 0.1;
        });
    }

    public static function findByCode(?string $code): ?self
    {
        return static::query()
            ->where('status', BusinessPartnerStatus::ACCEPTED)
            ->where('referral_code', $code)
            ->first();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(
            Business::class,
            'partner_merchant_mapping',
            'business_partner_id',
            'business_id',
        );
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class);
    }

    public function getReferralUrlAttribute(): string
    {
        return 'https://' . _env_domain('dashboard') . '/register?partner_referral=' . $this->referral_code;
    }

    public function getPricingItemsAttribute(): array
    {
        if(empty($this->pricing)) {
            return [
                [
                    'stripe_channel' => '',
                    'stripe_method' => '',
                    'stripe_percentage' => '',
                    'stripe_fixed_amount' => '',
                    'paynow_channel' => '',
                    'paynow_method' => '',
                    'paynow_percentage' => '',
                    'paynow_fixed_amount' => '',
                ],
            ];
        }

        return $this->pricing;
    }
}
