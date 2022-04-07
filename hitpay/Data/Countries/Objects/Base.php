<?php

namespace HitPay\Data\Countries\Objects;

use ArrayAccess;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

abstract class Base implements Arrayable, ArrayAccess
{
    protected string $country;

    protected array $rawData = [];

    protected array $data = [];

    protected array $children = [];

    /**
     * Base Constructor
     *
     * @param  string  $country
     */
    public function __construct(string $country)
    {
        $this->country = $country;
    }

    /**
     * Set raw data and process data.
     *
     * @param  array  $data
     *
     * @return $this
     */
    public function setData(array $data) : self
    {
        $this->rawData = $data;

        $this->data = $this->processData($data);

        return $this;
    }

    /**
     * Process data and set children if required.
     */
    protected function processData(array $data) : array
    {
        return $data;
    }

    /**
     * Get the data.
     *
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * Get the raw data.
     *
     * @return array
     */
    public function getRawData() : array
    {
        return $this->rawData;
    }

    /**
     * Set the child data.
     *
     * @param  string  $key
     * @param $value
     *
     * @return $this
     * @throws \Exception
     */
    protected function setChild(string $key, $value) : self
    {
        if (!( $value instanceof Base ) && !( $value instanceof Collection )) {
            $collectionClass = Collection::class;

            throw new Exception("The given value must implement object base or class '{$collectionClass}'.");
        }

        $this->children[$key] = $value;

        return $this;
    }

    /**
     * Unset the child data.
     *
     * @param  string  $key
     *
     * @return $this
     */
    protected function unsetChild(string $key) : self
    {
        unset($this->children[$key]);

        return $this;
    }

    /**
     * Get the child data.
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get the country.
     *
     * @return string
     */
    public function getCountry() : string
    {
        return $this->country;
    }

    /**
     * Get the attributes, magically.
     *
     * @param  string  $key
     *
     * @return mixed|null
     */
    public function __get(string $key)
    {
        return $this->data[$key] ?? $this->children[$key] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function toArray() : array
    {
        $children = $this->getChildren();

        foreach ($children as $key => $value) {
            if ($value instanceof Arrayable) {
                $children[$key] = $value->toArray();
            }
        }

        return array_merge($this->getData(), $children);
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
        return !!$this->{$offset};
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
     * @param  mixed  $offset
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value) : void
    {
        $this->data[$offset] = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     *
     * @return void
     */
    public function offsetUnset($offset) : void
    {
        unset($this->data[$offset]);
    }
}
