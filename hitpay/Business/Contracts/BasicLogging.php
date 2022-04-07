<?php

namespace HitPay\Business\Contracts;

interface BasicLogging
{
    /**
     * Get the logging group.
     *
     * @return string
     */
    public function getLoggingGroup() : string;
}
