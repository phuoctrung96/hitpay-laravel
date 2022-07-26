<?php

namespace App\Business;

use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Customer extends Model implements OwnableContract
{
    use Ownable, UsesUuid, Notifiable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_customers';

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 25;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    protected static function boot() : void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (!empty($model->attributes['country']) && Str::length($model->attributes['country']) > 2) {
                $country = $model->attributes['country'] == 'singapore'
                    ? 'sg'
                    : Str::substr($model->attributes['country'], 0, 2);

                $model->setAttribute('country', $country);
            }
        });
    }

    /**
     * Get the charges related to this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charges() : HasMany
    {
        return $this->hasMany(Charge::class, 'customer_email', 'email')->where('business_id', $this->business_id);
    }

    /**
     * Get the orders related to this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\Order|\App\Business\Order[]
     */
    public function orders() : HasMany
    {
        return $this->hasMany(Order::class, 'customer_email', 'email');
    }

    /**
     * Get the recurring plans related to this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\SubscriptionPlan|\App\Business\SubscriptionPlan[]
     */
    public function recurringBillings() : HasMany
    {
        return $this->hasMany(SubscriptionPlan::class, 'business_customer_id', 'id');
    }
}
