<?php

namespace App\Actions\Business;

use App\Actions\Action as BaseAction;
use App\Business as Model;
use HitPay\Data\Countries;
use HitPay\Data\Countries\Country;

abstract class Action extends BaseAction
{
    protected ?Model $business = null;

    protected ?string $businessId = null;

    protected ?Country $country = null;

    /**
     * Set the business.
     *
     * @param  \App\Business  $business
     *
     * @return $this
     */
    public function business(Model $business) : self
    {
        $this->business = $business;
        $this->businessId = $this->business->getKey();

        $this->country = Countries::get($this->business->country);

        return $this;
    }

    /**
     * Initiate a new instance starts with setting the business.
     *
     * @param  \App\Business  $business
     *
     * @return static
     */
    public static function withBusiness(Model $business) : self
    {
        return ( new static )->business($business);
    }
}
