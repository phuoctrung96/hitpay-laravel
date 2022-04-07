<?php

namespace App\Actions\User;

use Illuminate\Support\Facades\Log;

trait UserInfoByIp
{
    /**
     * @param string $purpose
     * @param string|null $ip
     * @param bool $deepDetect
     * @return array|string|null
     */
    protected function getUserInformationByIp(string $purpose = "location", string $ip = null, bool $deepDetect = true)
    {
        $output = null;

        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            $ip = $_SERVER["REMOTE_ADDR"];

            if ($deepDetect) {
                if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                    if (filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
                        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                    }
                }

                if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
                    if (filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
                        $ip = $_SERVER['HTTP_CLIENT_IP'];
                    }
                }
            }
        }

        $purpose = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));

        $support = array("country", "countrycode", "state", "region", "city", "location", "address");

        $continents = array(
            "AF" => "Africa",
            "AN" => "Antarctica",
            "AS" => "Asia",
            "EU" => "Europe",
            "OC" => "Australia (Oceania)",
            "NA" => "North America",
            "SA" => "South America"
        );

        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            try {
                $ipData = json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
            } catch (\Exception $exception) {
                Log::critical("Cant get information from geoplugin.net with IP " . $ip . ".
                    Default data information set as Singapore");

                return null;
            }

            if (isset($ipData->geoplugin_countryCode) && strlen(trim($ipData->geoplugin_countryCode)) == 2) {
                switch ($purpose) {
                    case "location":
                        $output = array(
                            "city" => $ipData->geoplugin_city ?? null,
                            "state" => $ipData->geoplugin_regionName ?? null,
                            "country" => $ipData->geoplugin_countryName ?? null,
                            "country_code" => $ipData->geoplugin_countryCode ?? null,
                        );

                        $continentCode = $ipData->geoplugin_continentCode ?? null;

                        $output['continent_code'] = $continentCode;

                        $output['continent'] = null;

                        if ($continentCode !== null) {
                            if (array_key_exists(strtoupper($continentCode), $continents)) {
                                $output['continent'] = $continents[$continentCode];
                            }
                        }

                        break;
                    case "address":
                        $address = array($ipData->geoplugin_countryName);

                        if (isset($ipData->geoplugin_regionName) && strlen($ipData->geoplugin_regionName) >= 1) {
                            $address[] = $ipData->geoplugin_regionName;
                        }

                        if (isset($ipData->geoplugin_city) && strlen($ipData->geoplugin_city) >= 1) {
                            $address[] = $ipData->geoplugin_city;
                        }

                        $output = implode(", ", array_reverse($address));

                        break;
                    case "city":
                        if (isset($ipData->geoplugin_city)) {
                            $output = $ipData->geoplugin_city;
                        }
                        break;
                    case "region":
                    case "state":
                        if (isset($ipData->geoplugin_regionName)) {
                            $output = $ipData->geoplugin_regionName;
                        }
                        break;
                    case "country":
                        if (isset($ipData->geoplugin_countryName)) {
                            $output = $ipData->geoplugin_countryName;
                        }
                        break;
                    case "countrycode":
                        $output = $ipData->geoplugin_countryCode;
                        break;
                }
            }
        }

        return $output;
    }
}
