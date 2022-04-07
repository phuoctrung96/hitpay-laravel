<?php

namespace App\Actions\Exceptions;

use Exception;

class BadRequest extends Exception
{
    protected bool $canBeIgnored = false;

    /**
     * BadRequest Constructor
     *
     * @param  string  $message
     * @param  bool  $canBeIgnored
     */
    public function __construct(string $message = "Bad Request", bool $canBeIgnored = false)
    {
        parent::__construct($message);

        $this->canBeIgnored = $canBeIgnored;
    }

    /**
     * Indicate if this exception can be ignored.
     *
     * @return bool
     */
    public function canBeIgnored() : bool
    {
        return $this->canBeIgnored;
    }
}
