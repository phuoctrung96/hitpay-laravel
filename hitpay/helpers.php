<?php

use App\Business;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CurrencyCode;
use App\Logics\ConfigurationRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Generate key name for the given columns.
 *
 * @param string $prefix
 * @param array $columns
 *
 * @return string
 */
function _blueprint_hash_columns(string $prefix, array $columns) : string
{
    return $prefix.'_'.substr(md5(implode('.', $columns)), 0, 8);
}

/**
 * Get domain or subdomain with or without protocol.
 *
 * WARNING:
 *
 * This method is calling another method `env()` to get the value of an environment variable (in the `.env` file). If
 * you call this method after the application configurations are cached may get an inconsistent value.
 *
 * @param string|null $subdomain
 * @param bool $withProtocol
 *
 * @return string
 */
function _env_domain(string $subdomain = null, bool $withProtocol = false) : string
{
    $domain = env('APP_DOMAIN', 'hit-pay.com');

    if (!is_null($subdomain)) {
        $domain = $subdomain.'.'.$domain;
    }

    if ($withProtocol) {
        $domain = 'https://'.$domain;
    }

    return $domain;
}

/**
 * Get additional domain or subdomain with or without protocol.
 *
 * WARNING:
 *
 * This method is calling another method `env()` to get the value of an environment variable (in the `.env` file). If
 * you call this method after the application configurations are cached may get an inconsistent value.
 *
 * @param string|null $subdomain
 * @param bool $withProtocol
 *
 * @return string
 */
function _env_shop_domain(string $subdomain = null, bool $withProtocol = false) : string
{
    $domain = env('SHOP_DOMAIN', 'myshop.sg');

    if (!is_null($subdomain)) {
        $domain = $subdomain.'.'.$domain;
    }

    if ($withProtocol) {
        $domain = 'https://'.$domain;
    }

    return $domain;
}

/**
 * Iterates over each value in the array passing them to the function recursively. If the callback function returns
 * true, the current value from array is returned into the result array. Array keys are preserved.
 *
 * @param array $array
 * @param \Closure $callback
 * @param int $flag
 *
 * @return array
 */
function array_filter_recursive(array $array, Closure $callback, int $flag = 0) : array
{
    foreach ($array as &$value) {
        if (is_array($value)) {
            $value = array_filter_recursive($value, $callback, $flag);
        }
    }

    return array_filter($array, $callback, $flag);
}

/**
 * Strip whitespace (or other characters) from the beginning and end of a string. Next search any one or more white
 * space character and replace it with the replacement given.
 *
 * @param string|null $string
 *
 * @return string|null
 */
function format_paragraph(?string $string) : ?string
{
    // Strip the whitespace (or other characters) from the beginning and the end of the string.
    $string = trim($string);
    // Next, limit the string to have maximum only 1 empty line break in between.
    $string = preg_replace('/(?:(?:\r|\n)\s*){2}/', "\n\n", $string);
    // Split the string at each line break point, into an array. Then trim the white space in each of them.
    $string = array_map(function ($value) {
        return trim_all($value);
    }, explode("\n", $string));
    // Finally, join back all the lines into a single string.
    $string = implode("\n", $string);

    return $string;
}

/**
 * Helper: Get config value by key. (Cache enabled)
 *
 * @param string $configurationKey
 * @param null $default
 *
 * @return mixed|null
 *
 * @see \App\Logics\ConfigurationRepository::get()
 */
function get_config(string $configurationKey, $default = null)
{
    return ConfigurationRepository::get($configurationKey, $default);
}

/**
 * Get country name by country code.
 *
 * @param string|null $countryCode
 * @param string|null $default
 *
 * @return string|null
 */
function get_country_name(?string $countryCode, string $default = null) : ?string
{
    if (!is_null($countryCode)) {
        $key = 'misc.country.'.$countryCode;

        if (Lang::has($key)) {
            return Lang::get($key);
        }
    }

    return $default;
}

/**
 * Converts a string containing an (IPv4) Internet Protocol dotted address into a proper address.
 *
 * @param string $address
 *
 * @return string|int|false
 */
function ipv4_long(string $address)
{
    return ip2long($address);
}

/**
 * Converts a string containing an (IPv6) Internet Protocol dotted address into a proper address.
 *
 * @param string $address
 *
 * @return string
 */
function ipv6_long(string $address)
{
    $bytes = inet_pton($address);
    $binary = '';

    for ($bits = 0; $bits <= 15; $bits++) {
        $binary .= sprintf('%08b', ord($bytes[$bits]));
    }

    return strlen($binary) ? gmp_strval(gmp_init($binary, 2)) : '0';
}

/**
 * Get the language code by locale.
 *
 * @param string $locale
 *
 * @return string
 */
function lang_code(string $locale) : string
{
    switch ($locale) {

        default:
            return 'en';
    }
}

/**
 * Limit the number of characters in a string.
 *
 * @param string|null $value
 * @param int $limit
 * @param string|null $end
 *
 * @return string|null
 */
function str_limit(?string $value, int $limit = 100, ?string $end = '…') : ?string
{
    return Str::limit($value, $limit, $end);
}

/**
 * Strip whitespace (or other characters) from the beginning and end of a string. Next, search any one or more white
 * space character and replace it with a whitespace.
 *
 * @param string|null $string
 *
 * @return string|null
 */
function trim_all(?string $string) : ?string
{
    return preg_replace('/\s+/', ' ', trim($string));
}

function validateUuid(string $string) //: bool
{
    return preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $string) > 0;
}

function getReadableAmountByCurrency(string $currency, int $amount, $default = null)
{
    if (CurrencyCode::isNormal($currency)) {
        return (float) bcdiv((string) $amount, '100', 2);
    } elseif (CurrencyCode::isZeroDecimal($currency)) {
        return $amount;
    }

    if ($default instanceof Closure) {
        return $default($currency, $amount);
    }

    return $default;
}

/**
 * @param string $currency
 * @param float $amount
 * @param null $default
 *
 * @return float|int|mixed|null
 * @throws \ReflectionException
 */
function getRealAmountForCurrency(string $currency, float $amount, $default = null)
{
    if (CurrencyCode::isNormal($currency)) {
        return (int) bcmul((string) $amount, '100');
    } elseif (CurrencyCode::isZeroDecimal($currency)) {
        return $amount;
    }

    if ($default instanceof Closure) {
        return $default($currency, $amount);
    }

    return $default;
}

/**
 * @param string $currency
 * @param int $amount
 * @param bool $withCurrencyCode
 *
 * @return int|string
 * @throws \ReflectionException
 */
function getFormattedAmount(string $currency, int $amount, bool $withCurrencyCode = true, $forFacebookFeed = false)
{
    $amount = number_format(getReadableAmountByCurrency($currency, $amount),
        CurrencyCode::isNormal($currency) ? 2 : 0);

    if ($withCurrencyCode) {
        return strtoupper($currency).' '.$amount;
    }
    if ($withCurrencyCode && $forFacebookFeed)
    {
        return $amount.' '.strtoupper($currency);
    }
    return $amount;
}

function sortArrayByPriorities(array $data, array $priorities)
{
    $sortedData = [];

    foreach ($priorities as $priority) {
        if (isset($rules[$priority])) {
            $sortedData[$priority] = Arr::pull($data, $priority);
        }
    }

    return $sortedData + $data;
}

function mapPluginFields($provider, array $data)
{
    switch ($provider) {
        case PluginProvider::WOOCOMMERCE:
        case PluginProvider::SHOPIFY:
            foreach ($data as $key => $val) {
                $data[str_replace('x_', '', $key)] = $val;
                unset($data[$key]);
            }

            $data['plugin_provider'] = $provider;

            return $data;
        break;
    }

    throw new \Exception('Invalid plugin provider ('.$provider.').');
}

function str_slug($title, $separator = '-', $language = 'en')
{
    return Str::slug($title, $separator, $language);
}

function generate_unique_slug($name)
{
    $slug = str_slug($name);

    $existingBusiness = Business::where('slug', $slug)->first();

    if ($existingBusiness instanceof Business) {
        for ($i=1; $i < 99; $i++) {

            $newSlug = $slug . '-' . $i;
            $existingBusiness = Business::where('slug', $newSlug)->first();

            if (!$existingBusiness instanceof Business) {

                return $newSlug;

            }
        }
    }

    return $slug;
}

function xero_branding_themes(Business $business): array
{
    $api = \App\Services\XeroApiFactory::makeAccountingApi($business);
    /** @var XeroAPI\XeroPHP\Models\Accounting\BrandingThemes $brandingThemes */
    $brandingThemes = $api->getBrandingThemes($business->xero_tenant_id);

    return $brandingThemes->getBrandingThemes();
}

function generateRandomString($length = 10) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
