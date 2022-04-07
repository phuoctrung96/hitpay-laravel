<?php

namespace App\Enumerations;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;

abstract class Enumeration
{
    /**
     * The constants cache.
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Enum constructor.
     */
    private function __construct()
    {
        // Silence is golden.
    }

    /**
     * Get the possible constants.
     *
     * @return array
     * @throws \ReflectionException
     */
    private static function getConstants() : array
    {
        if (!array_key_exists(static::class, self::$cache)) {
            self::$cache[static::class] = (new ReflectionClass(static::class))->getConstants();
        }

        return self::$cache[static::class];
    }

    /**
     * Check if the given value represents a valid constant.
     *
     * @param string $value
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function isValidValue(string $value) : bool
    {
        return in_array($value, self::getConstants(), true);
    }

    /**
     * Get the possible constant by the given value.
     *
     * @param string $value
     *
     * @return string|null
     * @throws \ReflectionException
     */
    public static function getConstantByValue(string $value) : ?string
    {
        if (self::isValidValue($value)) {
            return array_flip(self::getConstants())[$value];
        }

        return null;
    }

    /**
     * Check if the given name is a valid constant.
     *
     * @param string $name
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function isValidConstant(string $name) : bool
    {
        return array_key_exists($name, self::getConstants());
    }

    /**
     * Get the possible value by the given name.
     *
     * @param string $name
     *
     * @return string|int|null
     * @throws \ReflectionException
     */
    public static function getValueByName(string $name)
    {
        if (self::isValidConstant($name)) {
            return self::getConstants()[$name];
        }

        return null;
    }

    /**
     * Get the list of possible constants in array.
     *
     * @param array $excludedKeys
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function listConstants(array $excludedKeys = []) : array
    {
        return Arr::except(self::getConstants(), $excludedKeys);
    }

    /**
     * Get the list of possible constants in collection.
     *
     * @param array $excludedKeys
     *
     * @return \Illuminate\Support\Collection
     * @throws \ReflectionException
     */
    public static function collection(array $excludedKeys = []) : Collection
    {
        foreach (self::listConstants($excludedKeys) as $key => $value) {
            $data[] = compact('key', 'value');
        }

        return Collection::make($data ?? []);
    }
}
