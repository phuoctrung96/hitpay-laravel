<?php

namespace App\Actions\User;

use App\Actions\Action as BaseAction;
use App\Enumerations\CountryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
    protected function getDefaultCountries(bool $setActiveFalse = false): Collection
    {
        return Collection::make([
            [
                'id' => CountryCode::SINGAPORE,
                'name' => 'Singapore',
                'active' => !$setActiveFalse,
            ],
            [
                'id' => CountryCode::MALAYSIA,
                'name' => 'Malaysia',
                'active' => false,
            ]
        ]);
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
