<?php

namespace App\Business;

use Exception;
use HitPay\Business\BasicLogging;
use HitPay\Business\Contracts\BasicLogging as BasicLoggingContract;
use HitPay\Business\Contracts\Ownable as OwnableContract;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Shipping extends Model implements BasicLoggingContract, OwnableContract
{
    use BasicLogging, Ownable, UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_shippings';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'rate' => 'int',
        'formula' => 'array',
        'active' => 'bool',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    /**
     * The loggable attributes list.
     *
     * @var array
     */
    protected $loggableAttributes = [
        'name',
        'calculation',
        'rate',
        'formula',
        'active',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) : void {
            if (!array_key_exists('active', $model->attributes)) {
                $model->setAttribute('active', true);
            }
        });
    }

    /**
     * Indicate if subscribed feature is active.
     *
     * @return bool
     */
    public function isActive() : bool
    {
        return $this->active;
    }

    /**
     * Get the countries for this shipping.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Tax
     */
    public function countries() : HasMany
    {
        return $this->hasMany(ShippingCountry::class, 'business_shipping_id', 'id');
    }

    /**
     * Get the tax for this shipping.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\Tax
     */
    public function tax() : BelongsTo
    {
        return $this->belongsTo(Tax::class, 'business_tax_id', 'id', 'tax');
    }

    /**
     * Set countries.
     *
     * @param array $countries
     *
     * @return array
     * @throws \Exception
     */
    public function setCountries(array $countries) : array
    {
        if (!$this->exists) {
            throw new Exception('You can\'t set countries to a non-existing shipping.');
        }

        $currentCountries = $this->countries()->get();

        if (in_array('global', $countries)) {
            $countriesToBeRemoved = $currentCountries->toArray();
            $countriesToBeAdded = [];

            if (count($countriesToBeRemoved)) {
                $this->countries()->whereIn('country', $countriesToBeRemoved)->delete();

                $doLogging = true;
            }
        } else {
            $desiredCountries = (new Collection($countries))->map(function ($country) {
                return [
                    'business_shipping_id' => $this->getKey(),
                    'country' => $country,
                ];
            });

            $countriesToBeRemoved = $currentCountries
                ->whereNotIn('country', $desiredCountries->pluck('country')->toArray())->pluck('country')->toArray();

            $countriesToBeAdded = $desiredCountries
                ->whereNotIn('country', $currentCountries->pluck('country')->toArray());

            if (count($countriesToBeRemoved)) {
                $this->countries()->whereIn('country', $countriesToBeRemoved)->delete();

                $doLogging = true;
            }

            if ($countriesToBeAdded->count()) {
                $this->countries()->insert($countriesToBeAdded->toArray());

                $doLogging = true;
            }

            $countriesToBeAdded = $countriesToBeAdded->pluck('country')->toArray();
        }

        $attributes = [
            'added' => $countriesToBeAdded,
            'removed' => $countriesToBeRemoved,
        ];

        if ($doLogging ?? false) {
            $this->createLog('operation', 'countries_changed', $this->freshTimestamp(), array_filter($attributes));
        }

        return $attributes;
    }

    /**
     * Check if the shipping delivers to the given country.
     *
     * @param string $countries
     *
     * @return bool
     */
    public function hasCountry(string $country) : bool
    {
        if (!$this->relationLoaded('countries')) {
            $this->load('countries');
        }

        return $this->countries->where('country', $country)->count() > 0;
    }

    /**
     * Get the logging group.
     *
     * @return string
     */
    public function getLoggingGroup() : string
    {
        return 'operation';
    }
}
