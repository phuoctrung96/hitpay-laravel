<?php

namespace App\Business;

use App\Business;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\ChargeStatus;
use HitPay\Model\UsesUuid;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use App\Enumerations\Business\PaymentRequestStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Class PaymentRequest
 * @package App\Business
 *
 * @property bool $send_email
 * @property bool $email_status
 */
class PaymentRequest extends Model implements OwnableContract
{
    use Ownable, UsesUuid, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_payment_requests';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    protected $appends = [
        'current_status',
        'is_expired',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'payment_methods'   => 'array',
        'expiry_date'       => 'datetime'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'business_id',
        'amount',
        'name',
        'email',
        'phone',
        'currency',
        'status',
        'payment_methods',
        'purpose',
        'send_sms',
        'send_email',
        'is_default',
        'sms_status',
        'email_status',
        'webhook',
        'redirect_url',
        'reference_number',
        'allow_repeated_payments',
        'expiry_date',
        'commission_rate',
        'platform_business_id',
        'channel'
    ];

    public $charge_id;

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::created(function (self $model) : void {
            $business = Business::find($model->getAttribute('business_id'));

            if ($model->getAttribute('is_default') === null) {
                $model->setAttribute('url', _env_domain('securecheckout', true) . '/payment-request/@' . $business->slug . '/' . $model->getKey() . '/checkout');

                $model->save();
            }
        });
        static::retrieved(function (self $model) : void {
            if ($charge = $model->getPayments()->first())
                $model->charge_id = $charge->getKey();
        });
    }

    public function getCurrentStatusAttribute()
    {
        if ($this->getIsExpiredAttribute() && $this->status != PaymentRequestStatus::COMPLETED) {
            return PaymentRequestStatus::EXPIRED;
        }

        return $this->status;
    }

    public function getIsExpiredAttribute()
    {
        $now  = Carbon::now();

        if (!empty($this->expiry_date) && $now > $this->expiry_date) {
            return true;
        }

        return false;
    }

    public function getPayments(): Collection
    {
        return Charge::where('status', ChargeStatus::SUCCEEDED)
            ->where('plugin_provider_reference', $this->getKey())
            ->get();
    }
}
