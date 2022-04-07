<?php

namespace HitPay\Stripe;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support;
use Stripe;

class Collection implements Arrayable, ArrayAccess, Countable
{
    protected int $limit = 0;

    protected bool $hasMore = false;

    protected ?string $firstId = null;

    protected ?string $lastId = null;

    protected Support\Collection $items;

    /**
     * Collection Constructor
     *
     * @param  \Stripe\Collection  $collection
     * @param  int  $limit
     */
    public function __construct(Stripe\Collection $collection, int $limit)
    {
        $this->limit = $limit;
        $this->hasMore = $collection->has_more;

        $first = $collection->first();

        if ($first instanceof Stripe\StripeObject) {
            $this->firstId = $first->id;
        }

        $last = $collection->last();

        if ($last instanceof Stripe\StripeObject) {
            $this->lastId = $last->id;
        }

        $this->items = Support\Collection::make($collection->data);
    }

    /**
     * Get the limit of the collection.
     *
     * @return int
     */
    public function limit() : int
    {
        return $this->limit;
    }

    /**
     * Indicate whether the collection has next collection.
     *
     * @return bool
     */
    public function hasMore() : bool
    {
        return $this->hasMore;
    }

    /**
     * Get the first ID to retrieve the previous collection.
     *
     * @return string|null
     */
    public function firstId() : ?string
    {
        return $this->firstId;
    }

    /**
     * Get the last ID to retrieve the next collection.
     *
     * @return string|null
     */
    public function lastId() : ?string
    {
        return $this->lastId;
    }

    /**
     * Determine if the given item exists.
     *
     * @param  mixed  $key
     *
     * @return bool
     */
    public function offsetExists($key) : bool
    {
        return $this->items->has($key);
    }

    /**
     * Get the item at the given offset.
     *
     * @param  mixed  $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items->get($key);
    }

    /**
     * Set the item at the given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value) : void
    {
        $this->items->put($key, $value);
    }

    /**
     * Unset the item at the given key.
     *
     * @param  mixed  $key
     *
     * @return void
     */
    public function offsetUnset($key) : void
    {
        $this->items->forget($key);
    }

    /**
     * Get the number of items for the current batch.
     *
     * @return int
     */
    public function count() : int
    {
        return $this->items->count();
    }

    /**
     * Get the underlying collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCollection()
    {
        return $this->items;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'limit' => $this->limit(),
            'has_more' => $this->hasMore(),
            'last_id' => $this->lastId(),
            'data' => $this->items->toArray(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     *
     * @return string
     */
    public function toJson(int $options = 0) : string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
