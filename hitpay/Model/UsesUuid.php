<?php

namespace HitPay\Model;

use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait UsesUuid
{
    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType() : string
    {
        return 'string';
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return false
     */
    public function getIncrementing() : bool
    {
        return false;
    }

    /**
     * Boot the uses UUID trait for a model.
     */
    public static function bootUsesUuid() : void
    {
        static::creating(function (self $model) : void {
            if (empty($model->attributes[$model->getKeyName()])) {
                $model->setAttribute($model->getKeyName(), Str::orderedUuid()->toString());
            }
        });
    }
}
