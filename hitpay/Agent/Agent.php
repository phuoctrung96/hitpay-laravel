<?php

namespace HitPay\Agent;

use App\IPv4Geolocation;
use App\IPv6Geolocation;
use App\User;
use Closure;
use HitPay\Agent\Contracts\Geolocation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Facades\Agent as JenssegersAgent;

class Agent
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * The request cache.
     *
     * @var array
     */
    private $cachedRequest = [];

    /**
     * The data cache.
     *
     * @var array
     */
    private $cachedData = [];

    /**
     * The queries to be ignored.
     *
     * @var array
     */
    private $queriesToBeIgnored = [
        //
    ];

    /**
     * The requests to be ignored.
     *
     * @var array
     */
    private $requestsToBeIgnored = [
        '_token',
    ];

    /**
     * The requests to be masked.
     *
     * @var array
     */
    private $requestsToBeMasked = [
        'auth_token',
        'auth_code',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'password',
        'password_confirmation',
        'recovery_code',
    ];

    /**
     * Agent constructor.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        if (App::runningInConsole()) {

            // The `$this->request->ip()` method will only return localhost IP address if the activity is via SSH
            // connection. Hence, this code block is to detect if the activity is via SSH connection and try to get the
            // real IP address.
            //
            // NOTE:
            //
            // 1. `SSH_CONNECTION` and `SSH_CLIENT` are case sensitive and they must be in uppercase.
            // 2. If the server IP address is present, doesn't mean that it is the public IP address, it can be a mask
            //    in the private network. For example when hosting the application on EC2 on AWS, they are using VPC.

            $triedSshDetection = $this->request->server('SSH_CONNECTION', $this->request->server('SSH_CLIENT'));

            if (!is_null($triedSshDetection)) {
                $sshData = explode(' ', $triedSshDetection);

                if (isset($sshData[0]) && filter_var($sshData[0], FILTER_VALIDATE_IP)) {
                    $ipAddress = $sshData[0];

                    if (isset($sshData[2]) && filter_var($sshData[2], FILTER_VALIDATE_IP)) {
                        $this->cachedRequest['server_ip_address'] = $sshData[2];
                    }
                }
            }

            $method = 'console';
            $url = 'localhost';
        }

        $this->cachedRequest['ip_address'] = $ipAddress ?? $this->request->ip();
        $this->cachedRequest['method'] = $method ?? $this->request->method();
        $this->cachedRequest['url'] = $url ?? $this->request->url();
        $this->cachedRequest['user_agent'] = $this->request->userAgent();
        $this->cachedRequest['queries'] = $this->request->query();
        $this->cachedRequest['requests'] = $this->request->isMethod('get') ? [] : $this->request->post();
        $this->cachedRequest['referrer'] = $this->request->header('referer');
    }

    /**
     * Get the executor.
     *
     * @return \App\User|null
     */
    public function executor() : ?User
    {
        return $this->request->user();
    }

    /**
     * Get the IP address.
     *
     * @return string
     */
    public function ipAddress() : string
    {
        return $this->cachedRequest['ip_address'];
    }

    /**
     * Get the user agent.
     *
     * @return string|null
     */
    public function userAgent() : ?string
    {
        return $this->cachedRequest['user_agent'];
    }

    /**
     * Get the method.
     *
     * @return string
     */
    public function method() : string
    {
        return $this->cachedRequest['method'];
    }

    /**
     * Get the URL.
     *
     * @return string
     */
    public function url() : string
    {
        return $this->cachedRequest['url'];
    }

    /**
     * Get the geolocation of an IP address.
     *
     * @return \HitPay\Agent\Contracts\Geolocation|null
     */
    public function geolocation() : ?Geolocation
    {
        return $this->getDataHelper('geolocation', function () {
            return Cache::tags('geolocations')->remember($this->ipAddress(), 60 * 5, function () {
                try {
                    if (filter_var($this->ipAddress(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return IPv4Geolocation::findByAddress($this->ipAddress());
                    } elseif (filter_var($this->ipAddress(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        return IPv6Geolocation::findByAddress($this->ipAddress());
                    }
                } catch (QueryException $exception) {
                    //
                }

                return null;
            });
        });
    }

    /**
     * Get the browser name.
     *
     * @return string|null
     */
    public function browserName() : ?string
    {
        return $this->getDataHelper('browser.name', function () {
            return JenssegersAgent::browser();
        });
    }

    /**
     * Get the browser version.
     *
     * @return string|null
     */
    public function browserVersion() : ?string
    {
        return $this->getDataHelper('browser.version', function () {
            return JenssegersAgent::version($this->browserName());
        });
    }

    /**
     * Get the device type.
     *
     * @return string
     */
    public function deviceType() : string
    {
        // TODO - 2019-12-19
        // We will need to study and understand how the Agent detects device type and name, and we might need to add
        // extra rules for Agent to identify our mobile applications in the future, or we can submit the user-agent
        // header along the request.

        return $this->getDataHelper('device.type', function () {
            if (App::runningInConsole()) {
                return 'console';
            } elseif (JenssegersAgent::isMobile()) {
                if (JenssegersAgent::isTablet()) {
                    return 'tablet';
                }

                return 'phone';
            } elseif (JenssegersAgent::isRobot()) {
                return 'robot';
            }

            return 'computer';
        });
    }

    /**
     * Get the device name.
     *
     * @return string|null
     */
    public function deviceName() : ?string
    {
        return $this->getDataHelper('device.name', function () {
            return JenssegersAgent::device();
        });
    }

    /**
     * Get the platform name.
     *
     * @return string|null
     */
    public function platformName() : ?string
    {
        return $this->getDataHelper('platform.name', function () {
            return JenssegersAgent::platform();
        });
    }

    /**
     * Get the platform version.
     *
     * @return string|null
     */
    public function platformVersion() : ?string
    {
        return $this->getDataHelper('platform.version', function () {
            return JenssegersAgent::version($this->platformName());
        });
    }

    /**
     * Generate other data array.
     *
     * @param bool $withExecutorDetails
     *
     * @return array
     */
    public function others(bool $withExecutorDetails = true) : array
    {
        if (isset($this->cachedRequest['server_ip_address'])) {
            $data['server_ip_address'] = $this->cachedRequest['server_ip_address'];
        }

        $data['queries'] = Arr::except($this->cachedRequest['queries'], $this->queriesToBeIgnored);
        $data['requests'] = Arr::except($this->cachedRequest['requests'], $this->requestsToBeIgnored);

        foreach ($this->requestsToBeMasked as $key) {
            if (array_key_exists($key, $data['requests'])) {
                $data['requests'][$key] = '***HitPay Masked Data***';
            }
        }

        $data['referrer'] = $this->cachedRequest['referrer'];

        if ($withExecutorDetails && $executor = $this->executor()) {
            $data['executor'] = [
                'first_name' => $executor->first_name,
                'last_name' => $executor->last_name,
                'email' => $executor->email,
            ];
        }

        $data['device'] = [
            'type' => $this->deviceType(),
            'name' => $this->deviceName(),
        ];

        $data['platform'] = [
            'name' => $this->platformName(),
            'version' => $this->platformVersion(),
        ];

        $data['browser'] = [
            'name' => $this->browserName(),
            'version' => $this->browserVersion(),
        ];

        if ($geolocation = $this->geolocation()) {
            $data['location'] = implode(', ', array_unique(array_filter([
                $geolocation->city_name,
                $geolocation->region_name,
                $geolocation->country_name,
            ])));

            $data['coordinate'] = [
                'latitude' => $geolocation->latitude,
                'longitude' => $geolocation->longitude,
            ];
        }

        return array_filter_recursive($data, function ($value) : bool {
            return !empty($value);
        });
    }

    /**
     * Get the agent instance.
     *
     * @return self
     */
    public function instance() : self
    {
        return $this;
    }

    /**
     * Set the queries to be ignored
     *
     * @param array $queriesToBeIgnored
     *
     * @return $this
     */
    public function setQueriesToBeIgnored(array $queriesToBeIgnored) : self
    {
        $this->queriesToBeIgnored = array_unique(array_merge($this->queriesToBeIgnored, $queriesToBeIgnored));

        return $this;
    }

    /**
     * Set the requests to be masked.
     *
     * @param array $requestsToBeMasked
     *
     * @return $this
     */
    public function setRequestsToBeMasked(array $requestsToBeMasked) : self
    {
        $this->requestsToBeMasked = array_unique(array_merge($this->requestsToBeMasked, $requestsToBeMasked));

        return $this;
    }

    /**
     * Set the requests to be ignored.
     *
     * @param array $requestsToBeIgnored
     *
     * @return $this
     */
    public function setRequestsToBeIgnored(array $requestsToBeIgnored) : self
    {
        $this->requestsToBeIgnored = array_unique(array_merge($this->requestsToBeIgnored, $requestsToBeIgnored));

        return $this;
    }

    /**
     * The data get helper.
     *
     * @param string $key
     * @param \Closure $callback
     *
     * @return mixed
     */
    private function getDataHelper(string $key, Closure $callback)
    {
        if (!array_key_exists($key, $this->cachedData)) {
            $this->cachedData[$key] = $callback() ?: null;
        }

        return $this->cachedData[$key];
    }
}
