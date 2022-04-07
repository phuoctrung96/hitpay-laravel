<?php

namespace HitPay\Business;

use Illuminate\Support\Arr;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BasicLogging
{
    use LogHelpers;

    public static function bootBasicLogging() : void
    {
        static::created(function (self $model) : void {
            if (property_exists($model, 'loggableAttributes')) {
                $attributes = Arr::only($model->getAttributes(), $model->loggableAttributes);
            } else {
                $attributes = null;
            }

            $model->createLog($model->getLoggingGroup(), 'created', $model->getAttribute('created_at'), $attributes);
        });

        static::updated(function (self $model) : void {

            // We can use `static::getChanges()` because the changed attributes has been sync to `static::$changes`.

            $changes = $model->getChanges();

            $original = Arr::only($model->getOriginal(), array_keys($changes));

            // This is to prevent error when the original is missing. This scenario will happen when the model is just
            // created and get updated without refreshing the model.

            if ($model->wasRecentlyCreated) {
                foreach ($changes as $key => $value) {
                    if (!array_key_exists($key, $original)) {
                        $original[$key] = null;
                    }
                }
            }

            /////////////////////////////////////////////////////////////////////////////////////////////////////////
            //                                                                                                     //
            // NOTE: The sequence of the following checking is important, it makes the logs looks more reasonable. //
            //                                                                                                     //
            /////////////////////////////////////////////////////////////////////////////////////////////////////////

            // Here we will filter out and get what are the remaining attributes we will want to log as updated.
            //
            // NOTE: Do not include the attributes used in below events, we don't want to create redundant data.

            if (property_exists($model, 'loggableAttributes')) {
                $updates = Arr::only($changes, $model->loggableAttributes);
            }

            if (!empty($updates)) {
                foreach ($updates as $key => $value) {
                    $updates[$key] = [
                        'to' => $value,
                        'from' => $original[$key],
                    ];
                }

                $model->createLog($model->getLoggingGroup(), 'updated', $model->getAttribute('updated_at'), $updates);
            }
        });

        static::deleted(function (self $model) : void {
            if (property_exists($model, 'loggableAttributes')) {
                $attributes = Arr::only($model->getOriginal(), $model->loggableAttributes);
            } else {
                $attributes = null;
            }

            $model->createLog($model->getLoggingGroup(), 'deleted', $model->freshTimestamp(), $attributes);
        });
    }
}
