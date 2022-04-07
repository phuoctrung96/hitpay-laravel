<?php

namespace HitPay\MyInfoSG;

use GuzzleHttp\Client;
use Illuminate\Support\Facades;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as EncryptionCompactSerializer;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer as SignatureCompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Service
{
    private $clientId;

    private $clientSecret;

    private $authLevel;

    private $attributes;

    private $redirectUrl;

    private $apiBaseUrl;

    private $type;

    private $publicKeyPath;

    private $privateKeyPath;

    public function __construct(string $type)
    {
        $this->type = $type;

        $this->publicKeyPath = storage_path('SingPass/public.key');
        $this->privateKeyPath = storage_path('SingPass/private.key');
        $this->redirectUrl = Facades\URL::route('dashboard.business.verification.callback');

        if (Facades\App::environment('production')) {
            $prefix = '';
        } elseif (Facades\App::environment('local')) {
            $prefix = 'sandbox.';
            $this->publicKeyPath = storage_path('SingPass/sandbox/public.key');
            $this->privateKeyPath = storage_path('SingPass/sandbox/private.key');
            $this->redirectUrl = 'http://localhost:3001/callback';
        } else {
            $prefix = 'test.';
        }

        $baseUrl = 'https://'.$prefix.'api.myinfo.gov.sg/';

        if ($this->isBusiness()) {
            // I am not sur ewhy the v2 is not working.
            $this->apiBaseUrl = $baseUrl.'biz/v2';
            $this->attributes = implode(',', [
                'uinfin',
                'name',
                'sex',
                'residentialstatus',
                'nationality',
                'dob',
                'regadd',
                'email',
                'basic-profile',
                'addresses',
                'shareholders',
            ]);

            $this->clientId = Facades\Config::get('services.singpass.client_id');
            $this->clientSecret = Facades\Config::get('services.singpass.client_secret');
        } elseif ($this->isPersonal()) {
            $this->apiBaseUrl = $baseUrl.'com/v3';
            $this->attributes = implode(',', [
                'uinfin',
                'name',
                'sex',
                'residentialstatus',
                'nationality',
                'dob',
                'regadd',
                'email',
            ]);

            $this->clientId = Facades\Config::get('services.singpass.client_id_individual');
            $this->clientSecret = Facades\Config::get('services.singpass.client_secret_individual');
        } else {
            throw new NotFoundHttpException;
        }

        $this->authLevel = Facades\Config::get('services.singpass.auth_level');
    }

    private function verifyJsonWebSignature(string $accessToken)
    {
        $signature = (new JWSSerializerManager([new SignatureCompactSerializer]))->unserialize($accessToken);

        return json_decode($signature->getPayload(), true);
    }

    private function generateAuthHeader(
        string $url, array $parameters, string $method, string $contentType = null
    ) : string {
        if ($this->authLevel !== 'L2') {
            return '';
        }

        $nonce = random_int(PHP_INT_MIN, PHP_INT_MAX);

        $timestamp = (int) round(microtime(true) * 1000);

        $defaultApexHeaders = [
            'app_id' => $this->clientId,
            'nonce' => $nonce,
            'signature_method' => 'RS256',
            'timestamp' => $timestamp,
        ];

        if ($method === 'POST' && $contentType !== 'application/x-www-form-urlencoded') {
            $parameters = [];
        }

        $baseParameters = array_merge($defaultApexHeaders, $parameters);

        ksort($baseParameters);

        $baseString = implode('&', [$method, $url, urldecode(http_build_query($baseParameters))]);

        $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath));

        openssl_sign($baseString, $signature, $privateKey, 'sha256WithRSAEncryption');

        return 'PKI_SIGN timestamp="'.$timestamp.'",nonce="'.$nonce.'",app_id="'.$this->clientId.'",signature_method="RS256",signature="'.base64_encode($signature).'"';
    }

    private function decryptJsonWebEncryption(string $personDataToken)
    {
        $jsonWebKey = JWKFactory::createFromKeyFile($this->privateKeyPath);
        $jsonWebEncryption = (new JWESerializerManager([
            new EncryptionCompactSerializer,
        ]))->unserialize($personDataToken);

        $jsonWebEncryptionDecrypter = new JWEDecrypter(
            new AlgorithmManager([new RSAOAEP]),
            new AlgorithmManager([new A256GCM]),
            new CompressionMethodManager([new Deflate])
        );

        $jsonWebEncryptionDecrypter->decryptUsingKey($jsonWebEncryption, $jsonWebKey, 0);

        return str_replace('"', '', $jsonWebEncryption->getPayload());
    }

    public function getAuthoriseUrl(string $state) : string
    {
        $query = urldecode(http_build_query([
            'client_id' => $this->clientId,
            'attributes' => $this->attributes,
            'purpose' => Facades\Config::get('app.debug') ? 'Know our customer (staging)' : 'Know our customer',
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
        ]));
//dd($this->apiBaseUrl.'/authorise?'.$query);
        return $this->apiBaseUrl.'/authorise?'.$query;
    }

    public function getAccessToken(string $code)
    {
        $contentType = 'application/x-www-form-urlencoded';
        $method = 'POST';

        $parameters = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
        ];

        $headers = [
            'Accept-Encoding' => 'gzip',
            'Cache-Control' => 'no-cache',
            'Content-Type' => $contentType,
        ];

        $apiTokenUrl = $this->apiBaseUrl.'/token/';

        if ($this->authLevel === 'L2') {
            $headers['Authorization'] = $this->generateAuthHeader($apiTokenUrl, $parameters, $method, $contentType);
        }

        $tokenRequestResponseBody = (new Client)->post($apiTokenUrl, [
            'form_params' => $parameters,
            'headers' => $headers,
        ])->getBody();

        if ($tokenRequestResponseBody) {
            $decoded = json_decode($tokenRequestResponseBody, true);

            if (isset($decoded['access_token'])) {
                return $decoded['access_token'];
            }
        }

        throw new Exceptions\AccessTokenNotFoundException;
    }

    public function getInformation($accessToken)
    {
        $payload = $this->verifyJsonWebSignature($accessToken);

        if (is_null($payload)) {
            throw new Exceptions\InvalidAccessTokenException;
        } elseif (!isset($payload['sub']) || is_null($payload['sub'])) {
            throw new Exceptions\SubNotFoundException;
        }
        if ($this->isPersonal()) {
            $apiPersonUrl = $this->apiBaseUrl.'/person/'.$payload['sub'];
        } elseif ($this->isBusiness()) {
            $uen = explode('_', $payload['sub']);

            $apiPersonUrl = $this->apiBaseUrl.'/entity-person/'.$uen[0].'/'.$uen[1];
        }

        $parameters = [
            'client_id' => $this->clientId,
            'attributes' => $this->attributes,
        ];

        $headers = [
            'Cache-Control' => 'no-cache',
            'Accept-Encoding' => 'gzip',
        ];

        $authHeaders = $this->generateAuthHeader($apiPersonUrl, $parameters, 'GET');

        if ($authHeaders) {
            $headers['Authorization'] = $authHeaders.',Bearer '.$accessToken;
        } else {
            $headers['Authorization'] = 'Bearer '.$accessToken;
        }

        $personRequestResponse = (new Client)->get($apiPersonUrl, [
            'query' => $parameters,
            'headers' => $headers,
        ])->getBody();

        if (!$personRequestResponse) {
            throw new Exceptions\MyInfoPersonDataNotFoundException;
        }

        if ($this->authLevel === 'L2') {
            if (is_null($personDataJsonWebSignature = $this->decryptJsonWebEncryption($personRequestResponse))) {
                throw new Exceptions\InvalidDataOrSignatureForPersonDataException;
            } elseif (is_null($decodedPersonData = $this->verifyJsonWebSignature($personDataJsonWebSignature))) {
                throw new Exceptions\InvalidDataOrSignatureForPersonDataException;
            }

            $data = ['data' => $decodedPersonData];
        } else {
            $data = ['data' => json_decode($personRequestResponse, true)];
        }

        if (isset($uen)) {
            $data = array_merge($data, [
                'uinfin' => $uen[1],
                'uen' => $uen[0],
            ]);
        } else {
            $data = array_merge($data, [
                'uinfin' => $payload['sub'],
            ]);
        }

        return $data;
    }

    public function isPersonal()
    {
        return $this->type === 'personal';
    }

    public function isBusiness()
    {
        return $this->type === 'business';
    }
}
