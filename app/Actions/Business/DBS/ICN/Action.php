<?php

namespace App\Actions\Business\DBS\ICN;

use App\Actions\Action as BaseAction;

abstract class Action extends BaseAction
{
    protected string $filepath;

    protected string $reference;

    /**
     * Set the filepath.
     *
     * @param  string  $filepath
     *
     * @return $this
     */
    public function filepath(string $filepath) : self
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * Initiate a new instance starts with setting the filepath.
     *
     * @param  string  $filepath
     *
     * @return static
     */
    public static function withFilepath(string $filepath) : self
    {
        return ( new static )->filepath($filepath);
    }

    /**
     * Set the reference.
     *
     * @param  string  $reference
     *
     * @return $this
     */
    public function reference(string $reference) : self
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Initiate a new instance starts with setting the reference.
     *
     * @param  string  $reference
     *
     * @return static
     */
    public static function withReference(string $reference) : self
    {
        return ( new static )->reference($reference);
    }
}
