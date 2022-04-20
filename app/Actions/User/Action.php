<?php

namespace App\Actions\User;

use App\Actions\Action as BaseAction;
use App\Enumerations\CountryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lang;

abstract class Action extends BaseAction
{
    protected Request $request;

    /**
     * Initiate a new instance starts with setting the request data.
     *
     * @param Request $data
     *
     * @return static
     */
    public static function withRequest(Request $data) : self
    {
        return ( new static )->setRequest($data);
    }

    /**
     * @return Collection
     */
    protected function getDefaultCountries(): Collection
    {
        $countries = array_map(function($country_code) {
            return [
                'id' => $country_code,
                'name' => Lang::has('misc.country.'.$country_code) ? Lang::get('misc.country.'.$country_code) : $country_code,
                'active' => false
            ];
        }, CountryCode::listConstants());

        return Collection::make(array_values($countries));
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request) : self
    {
        $this->request = $request;

        return $this;
    }
}
