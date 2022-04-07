<?php

namespace HitPay\Data\Objects;

use Exception;
use Illuminate\Contracts\Support\Arrayable;

abstract class Base implements Arrayable, \ArrayAccess
{
    protected array $data = [];

    /**
     * Get the attributes, magically.
     *
     * @param  string  $key
     *
     * @return mixed|null
     */
    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Set the attributes, magically.
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return void
     * @throws \Exception
     */
    public function __set(string $key, $value) : void
    {
        throw new Exception("You can not set the data for this class.");
    }

    /**
     * @inheritdoc
     */
    public function toArray() : array
    {
        return $this->data;
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     *
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return key_exists($offset, $this->data);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * Set the value for a given offset.
     *
     * @param $offset
     * @param $value
     *
     * @return void
     * @throws \Exception
     */
    public function offsetSet($offset, $value) : void
    {
        throw new Exception("You can not set the data for this class.");
    }

    /**
     * Unset the value for a given offset.
     *
     * @param $offset
     *
     * @return void
     * @throws \Exception
     */
    public function offsetUnset($offset) : void
    {
        throw new Exception("You can not unset the data for this class.");
    }
}
