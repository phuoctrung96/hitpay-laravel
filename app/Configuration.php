<?php

namespace App;

use App\Exceptions\ModelNotUpdatableException;
use App\Exceptions\ModelRuntimeException;
use Carbon\Carbon;
use Exception;
use HitPay\Model\UsesUuid;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Configuration extends Model
{
    use UsesUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'configurations';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'autoload' => 'bool',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        //
    ];

    /**
     * The available types.
     *
     * @var array
     */
    private $allowedTypes = [
        'array',
        'bool',
        'collection',
        'date',
        'datetime',
        'decimal',
        'double',
        'int',
        'string',
        'timestamp',
    ];

    /**
     * The loggable attributes list.
     *
     * @var array
     */
    protected $loggableAttributes = [
        'configuration_key',
        'type',
        'value',
        'autoload',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot() : void
    {
        parent::boot();

        static::updating(function (self $model) : void {
            foreach ([
                'configuration_key',
                'type',
                'autoload',
            ] as $value) {
                if ($model->isDirty($value)) {
                    throw ModelNotUpdatableException::forAttribute($model, $value);
                }
            }
        });

        static::created(function (self $model) {
            $model->createLog('created', $model->getAttribute('created_at'),
                Arr::only($model->getAttributes(), $model->loggableAttributes));
        });

        static::updated(function (self $model) {

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

            $updates = Arr::only($changes, $model->loggableAttributes);

            if (!empty($updates)) {
                foreach ($updates as $key => $value) {
                    $updates[$key] = [
                        'to' => $value,
                        'from' => $original[$key],
                    ];
                }

                $model->createLog('updated', $model->getAttribute('updated_at'), $updates);
            }
        });

        static::saved(function (self $model) {
            Cache::forget(get_class($model));
        });

        static::deleted(function (self $model) {
            $model->createLog('deleted', $model->freshTimestamp(), Arr::only($model->getOriginal(), $model->loggableAttributes));

            Cache::forget(get_class($model));
        });
    }

    /**
     * Set mutator for type attribute.
     *
     * @param string $value
     *
     * @throws \Exception
     */
    public function setTypeAttribute(string $value) : void
    {
        if (isset($this->attributes['value'])) {
            throw new ModelRuntimeException('Value is already set, cannot change value type.');
        } elseif (!preg_match('/^decimal:\d$/', $value) && !in_array($value, $this->allowedTypes)) {
            throw new ModelRuntimeException('Value type is invalid. Type given: '.$value);
        }

        $this->attributes['type'] = $value;
    }

    /**
     * Set mutator for value attribute.
     *
     * @param $value
     *
     * @throws \Exception
     */
    public function setValueAttribute($value) : void
    {
        if (is_null($this->type)) {
            throw new ModelRuntimeException('Value type is not set, cannot set value.');
        }

        if (in_array($this->type, [
            'date',
            'datetime',
        ])) {
            $value = $this->fromDateTime($value);
        } elseif (in_array($this->type, [
            'array',
            'collection',
            'json',
            'object',
        ])) {
            $value = $this->asJson($value);

            if ($value === false) {
                throw new JsonEncodingException(sprintf('Unable to encode attribute [value], key [%s] for model [%s] to JSON: %s.',
                    $this->getKey() ?? 'null', static::class, json_last_error_msg()));
            }
        }

        $this->attributes['value'] = $value;
    }

    /**
     * Get mutator for value attribute.
     *
     * @return mixed
     */
    public function getValueAttribute()
    {
        if (is_null($this->type) || !isset($this->attributes['value'])) {
            return null;
        } elseif (Str::startsWith($this->type, 'decimal')) {
            return $this->asDecimal($this->attributes['value'], explode(':', $this->type, 2)[1] ?? 2);
        }

        switch ($this->type) {

            case 'array':
            case 'json':
                return $this->fromJson($this->attributes['value']);

            case 'bool':
                return (bool) $this->attributes['value'];

            case 'collection':
                return new Collection($this->fromJson($this->attributes['value']));

            case 'date':
                return $this->asDate($this->attributes['value']);

            case 'datetime':
                return $this->asDateTime($this->attributes['value']);

            case 'double':
                return $this->fromFloat($this->attributes['value']);

            case 'int':
                return (int) $this->attributes['value'];

            case 'object':
                return $this->fromJson($this->attributes['value'], true);

            case 'string':
                return (string) $this->attributes['value'];

            case 'timestamp':
                return $this->asTimestamp($this->attributes['value']);
        }

        return $this->attributes['value'];
    }

    /**
     * Get the related system logs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Log|\App\Log[]
     */
    public function logs() : MorphMany
    {
        return $this->morphMany(Log::class, 'log', 'associable_type', 'associable_id', 'id');
    }

    /**
     * Overridden to disable conversion of the model instance to an array.
     *
     * @throws \Exception
     */
    public function toArray() : void
    {
        throw new Exception('This method has been overridden to disable conversion of the model instance to an array.');
    }

    /**
     * Get logging group.
     *
     * @return string|null
     */
    public function loggingGroup() : ?string
    {
        if ($key = $this->configuration_key) {
            switch (true) {

                case Str::startsWith($key, 'info_'):
                case Str::startsWith($key, 'site_default_'):
                    return 'information';

                case in_array($key, [
                    'is_user_can_register',
                ]):
                    return 'operation';

                default:
                    return 'unknown';
            }
        }

        return null;
    }

    /**
     * Create log for the configuration.
     *
     * @param string $event
     * @param \Illuminate\Support\Carbon $loggedAt
     * @param array|null $attributes
     *
     * @throws \Exception
     */
    protected function createLog(string $event, Carbon $loggedAt, array $attributes = null) : void
    {
        $this->logs()->make([
            'group' => $this->loggingGroup() ?? 'unset',
            'event' => $event,
            'logged_at' => $loggedAt,
        ])->logAttributes($attributes)->save();
    }
}
