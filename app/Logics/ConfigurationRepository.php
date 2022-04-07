<?php

namespace App\Logics;

use App\Configuration as Model;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

class ConfigurationRepository
{
    /**
     * The configurations cache.
     *
     * @var array|null
     */
    public static $cache;

    /**
     * Helper: Get config value by key. (Cache enabled)
     *
     * @param string $configKey
     * @param \Closure|mixed|null $default
     *
     * @return mixed|null
     */
    public static function get(string $configKey, $default = null)
    {
        // NOTE: Do not set any default value when getting cache, let it be null. Null will allow data to be loaded from
        // database and cache it. When caching the value, do not set null as value even if nothing to be cached. Setting
        // null will cause a loop for caching.

        if (!is_array(static::$cache)) {
            $cache = Cache::get(Model::class);

            if (is_array($cache)) {
                static::$cache = $cache;
            } else {
                Model::where('autoload', true)->each(function (Model $config) use (&$cache) {
                    static::$cache[$config->configuration_key] = $config->value;
                });

                if (!is_array(static::$cache)) {
                    static::$cache = [];
                }

                Cache::put(Model::class, static::$cache, Date::now()->addHour());
            }
        }

        if (array_key_exists($configKey, static::$cache)) {
            return static::$cache[$configKey];
        } elseif ($default instanceof Closure) {
            return $default($configKey);
        }

        return $default;
    }
}
