<?php

namespace App\Models\Business;

use HitPay\Business;
use HitPay\Data\Countries;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model implements Business\Contracts\Ownable
{
    use Business\Ownable, SoftDeletes, UsesUuid;

    /**
     * @inheritdoc
     */
    protected $table = 'business_bank_accounts';

    /**
     * @inheritdoc
     */
    protected $casts = [
        'data' => 'array',
        'stripe_external_account_default' => 'boolean',
    ];

    /**
     * @inheritdoc
     */
    protected $appends = [
        'bank_id',
        'bank_name',
        'bank_code',
        'branch_code',
        'use_in_hitpay',
        'use_in_stripe',
    ];

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $bankAccount) {
            if (!array_key_exists('hitpay_default', $bankAccount->attributes)) {
                $bankAccount->setAttribute('hitpay_default', false);
            }

            if (!array_key_exists('stripe_external_account_default', $bankAccount->attributes)) {
                $bankAccount->setAttribute('stripe_external_account_default', false);
            }
        });
    }

    /**
     * Get the bank ID.
     *
     * @return string|null
     */
    public function getBankIdAttribute() : ?string
    {
        return $this->data['data']['bank']['id'] ?? $this->bank_swift_code ?? null;
    }

    /**
     * Get the bank name.
     *
     * @return string
     */
    public function getBankNameAttribute() : string
    {
        return $this->getBank()['name'] ?? $this->data['data']['bank']['name'] ?? 'Unknown';
    }

    /**
     * Get the bank code.
     *
     * @return string|null
     */
    public function getBankCodeAttribute() : ?string
    {
        return $this->analyseBankRoutingNumber()[0];
    }

    /**
     * Get the branch code.
     *
     * @return string|null
     */
    public function getBranchCodeAttribute() : ?string
    {
        return $this->analyseBankRoutingNumber()[1];
    }

    /**
     * Get the indicator whether this is use in HitPay.
     *
     * @return bool
     */
    public function getUseInHitPayAttribute() : bool
    {
        return $this->hitpay_default;
    }

    /**
     * Get the indicator whether this is use in Stripe.
     *
     * @return bool
     */
    public function getUseInStripeAttribute() : bool
    {
        return $this->stripe_external_account_default;
    }

    /**
     * Helper to analyse bank routing number.
     *
     * @return false|null[]|string[]
     */
    private function analyseBankRoutingNumber()
    {
        if ($this->bank_routing_number) {
            $routingNumber = explode('-', $this->bank_routing_number);

            if (count($routingNumber) === 2) {
                return $routingNumber;
            }
        }

        return [ null, null ];
    }

    /**
     * Get the related bank.
     *
     * @return \HitPay\Data\Countries\Objects\Bank|null
     */
    private function getBank() : ?Countries\Objects\Bank
    {
        return Countries::get($this->country)->banks()->where('id', $this->bank_id)->first();
    }
}
