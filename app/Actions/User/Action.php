<?php

namespace App\Actions\User;

use App\Actions\Action as BaseAction;
use Illuminate\Http\Request;

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
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request) : self
    {
        $this->request = $request;

        return $this;
    }
}
