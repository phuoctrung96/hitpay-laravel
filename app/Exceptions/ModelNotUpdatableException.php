<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

class ModelNotUpdatableException extends Exception
{
    /**
     * Create a new not updatable exception for an attribute.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     *
     * @return static
     */
    public static function forAttribute(Model $model, string $key) : self
    {
        return new static(sprintf('It is prohibited to update the %s of [%s].', $key, get_class($model)));
    }
}

