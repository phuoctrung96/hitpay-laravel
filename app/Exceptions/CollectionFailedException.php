<?php

namespace App\Exceptions;

use Exception;

class CollectionFailedException extends Exception
{
    protected $declineCode;

    protected $response;

    public function __construct(string $declineCode, array $response)
    {
        parent::__construct('Collection failed.');

        $this->declineCode = $declineCode;
        $this->response = $response;
    }

    /**
     * Gets the decline code.
     *
     * @return string
     */
    public function getDeclineCode()
    {
        return $this->declineCode;
    }

    /**
     * Gets the response.
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }
}
