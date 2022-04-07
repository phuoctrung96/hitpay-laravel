<?php

namespace App\Actions;

use Illuminate\Support\Facades;

abstract class Action
{
    protected array $data = [];

    /**
     * Set the data.
     *
     * @param  array  $data
     *
     * @return $this
     */
    public function data(array $data) : self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Initiate a new instance starts with setting the data.
     *
     * @param  array  $data
     *
     * @return static
     */
    public static function withData(array $data) : self
    {
        return ( new static )->data($data);
    }

    /**
     * Helper to get validation error message.
     *
     * @param  string  $attribute
     * @param  string  $rule
     *
     * @return string
     */
    protected function validationErrorMessage(string $attribute, string $rule) : string
    {
        $_attributeLangeKey = "validation.attributes.{$attribute}";

        if (Facades\Lang::has($_attributeLangeKey)) {
            $attribute = Facades\Lang::get($_attributeLangeKey);
        }

        return Facades\Lang::get("validation.{$rule}", compact('attribute'));
    }
}
