<?php

namespace HitPay\Verification\Cognito;

use App\Business;
use GuzzleHttp\Client;
use Illuminate\Support\Facades;

abstract class Service
{
    protected string $version = '2020-08-14';
    protected string $apiKey;
    protected string $apiSecret;
    protected string $apiUrl;
    protected string $apiUri;
    protected string $requestTarget;
    protected string $contentType = 'application/vnd.api+json';
    protected string $acceptType = 'application/vnd.api+json';
    protected ?array $bodyParams = null;
    protected array $authenticationHeaders = [];
    protected string $method = 'get';
    protected string $templateId;
    protected Business $business;

    public function __construct()
    {
        $this->apiKey = Facades\Config::get('services.cognito.key');
        $this->apiSecret = Facades\Config::get('services.cognito.secret');
        $this->apiUrl = Facades\Config::get('services.cognito.api_url');
        $this->templateId = Facades\Config::get('services.cognito.template_id');
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method='get') : self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $uri
     * @return $this
     */
    public function setUri(string $uri) : self
    {
        $this->apiUri = $uri;

        return $this;
    }

    /**
     * @param string $requestTarget
     * @return $this
     */
    public function setRequestTarget(string $requestTarget) : self
    {
        $this->requestTarget = $requestTarget;

        return $this;
    }

    /**
     * @param array|null $bodyParam
     * @return $this
     */
    public function setBodyParams(?array $bodyParam) : self
    {
        $this->bodyParams = $bodyParam;

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function generateAuthenticationHeader() : self
    {
        // authentication https://cognitohq.com/docs/guides/authenticating
        if ($this->requestTarget == null) {
            throw new \Exception("Request target need to set");
        }

        if ($this->bodyParams == null)  {
            $body = http_build_query($this->bodyParams);
        } else {
            $body = json_encode($this->bodyParams);
        }

        # Generates a digest using the request body
        $digest = 'SHA-256=' . base64_encode(openssl_digest($body, 'sha256', true));

        # Generates a date in this format: Thu, 25 Aug 2016 22:37:14 GMT
        $date = gmdate('D, d M Y H:i:s T');

        # Generates the signing string. Note that the parts of the string are
        # concatenated with a newline character
        $signingString = "(request-target): ". $this->requestTarget . PHP_EOL . "date: " . $date . PHP_EOL . "digest: " . $digest;

        # Creates the HMAC-SHA256 digest using the API secret and then base64 encodes that value
        $signature = base64_encode(hash_hmac('sha256', $signingString, $this->apiSecret, true));

        # Creates the authorization header and concatenates it together using a comma
        $authorization = join(",", [
            'Signature keyId="' . $this->apiKey . '"',
            'algorithm="hmac-sha256"',
            'headers="(request-target) date digest"',
            'signature="' . $signature . '"'
        ]);

        $this->authenticationHeaders = [
            'Date' => $date,
            'Digest' => $digest,
            'Authorization' => $authorization,
            'Content-Type' => $this->contentType,
            'Accept' => $this->acceptType,
            'Cognito-Version' => $this->version,
        ];

        if (!Facades\App::environment('production')) {
            Facades\Log::info(print_r('bodyParams', true));
            Facades\Log::info(print_r($this->bodyParams, true));

            Facades\Log::info(print_r('body', true));
            Facades\Log::info(print_r($body, true));

            Facades\Log::info(print_r('digest', true));
            Facades\Log::info(print_r($digest, true));

            Facades\Log::info(print_r('date', true));
            Facades\Log::info(print_r($date, true));

            Facades\Log::info(print_r('signingString', true));
            Facades\Log::info(print_r($signingString, true));

            Facades\Log::info(print_r('signature', true));
            Facades\Log::info(print_r($signature, true));

            Facades\Log::info(print_r('authorization', true));
            Facades\Log::info(print_r($authorization, true));
        }

        return $this;
    }

    /**
     * @param Business $business
     * @return $this
     */
    public function setBusiness(Business $business) : self
    {
        $this->business = $business;

        return $this;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function process()
    {
        if ($this->method == 'get') {
            try {
                $response = (new Client)->get($this->apiUrl . $this->apiUri, [
                    'form_params' => $this->bodyParams,
                    'headers' => $this->authenticationHeaders,
                ])->getBody();

                return json_decode($response, true);
            } catch (\Exception $exception) {
                Facades\Log::info($exception->getMessage());
                Facades\Log::info(print_r($exception->getResponse()->getBody()->getContents(), true));
            }
        } elseif ($this->method == 'post') {
            try {
                $response = (new Client)->post($this->apiUrl . $this->apiUri, [
                    'json' => $this->bodyParams,
                    'headers' => $this->authenticationHeaders,
                ])->getBody();

                return json_decode($response, true);
            } catch (\Exception $exception) {
                Facades\Log::critical($exception->getMessage());
                Facades\Log::info(print_r($exception->getResponse()->getBody()->getContents(), true));
            }
        } else {
            throw new \Exception("Undefined method $this->method when call cognito service with id");
        }
    }
}
