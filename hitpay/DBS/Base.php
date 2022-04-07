<?php

namespace HitPay\DBS;

use Crypt_GPG;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use Throwable;

abstract class Base
{
    protected $isProduction;

    protected $isDebugging;

    protected $organizationID;

    protected $apiKey;

    protected $corporateName;

    protected $corporateAccountNumber;

    protected $datetime;

    protected $messageId;

    protected $baseUrl;

    protected $shortCode;

    protected $requestBody;

    protected $responseBody;

    protected $logPath;

    public function __construct()
    {
        $this->isProduction = Facades\App::environment('production');
        $this->isDebugging = Facades\Config::get('app.debug');
        $this->organizationID = Facades\Config::get('services.dbs.organization_id'); // Maximum 12 characters
        $this->apiKey = Facades\Config::get('services.dbs.api_key');
        $this->corporateName = Facades\Config::get('services.dbs.corporate_name');
        $this->corporateAccountNumber = Facades\Config::get('services.dbs.corporate_account_number');
        $this->datetime = Facades\Date::now();

        $uuidArray = explode('-', Str::orderedUuid()->toString());

        array_shift($uuidArray);

        $this->messageId = $this->datetime->format('Ymd').implode('', $uuidArray);
    }

    protected function sendRequest() : void
    {
        Facades\Storage::append($this->logPath, implode("\n", [
            '======================',
            '= Plain Request Body =',
            '======================',
            json_encode($this->requestBody, JSON_PRETTY_PRINT),
        ]));

        try {
            $responseBodyContents = ( new Client([ 'verify' => false ]) )->post($this->baseUrl, [
                'headers' => [
                    'X-API-KEY' => $this->apiKey,
                    'X-DBS-ORG_ID' => $this->organizationID,
                    'Content-Type' => 'text/plain',
                ],
                'body' => $this->encryptMessage(json_encode($this->requestBody)),
            ])->getBody()->getContents();
        } catch (Throwable $throwable) {
            if (!( $throwable instanceof ClientException )) {
                $throwableClassName = get_class($throwable);

                Facades\Log::critical("Unexpected exception '{$throwableClassName}' caught when calling DBS Refund API. For details, please refer to '{$this->logPath}' in S3.\n\n{$throwable->getTraceAsString()}");
            }

            throw $throwable;
        }

        Facades\Storage::append($this->logPath, implode("\n", [
            '======================',
            '= Encrypted Response =',
            '======================',
            $responseBodyContents,
        ]));

        $this->responseBody = $this->decryptMessage($responseBodyContents);

        Facades\Storage::append($this->logPath, implode("\n", [
            '======================',
            '= Decrypted Response =',
            '======================',
            json_encode($this->responseBody, JSON_PRETTY_PRINT),
        ]));
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function getRequestBody()
    {
        return $this->requestBody;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }

    protected function mapBankSwiftCode(string $bankSwiftCode) : string
    {
        return $this->isProduction ? $bankSwiftCode : substr_replace($bankSwiftCode, '0', 7, 1);
    }

    protected function newCryptGPG()
    {
        return new Crypt_GPG([
            'homedir' => '/home/ubuntu/.gnupg',
            'digest-algo' => 'SHA256',
            'cipher-algo' => 'AES256',
            'compress-algo' => 'zip',
            'debug' => $this->isDebugging,
        ]);
    }

    protected function encryptMessage(string $plainMessage)
    {
        $gpg = $this->newCryptGPG();

        $publicKey = $gpg->importKey(file_get_contents(storage_path('dbs.key')));

        $gpg->addEncryptKey($publicKey['fingerprint']);

        $encrypt = $gpg->importKey(file_get_contents(storage_path('private.key')));

        $gpg->addSignKey($encrypt['fingerprint']);
        $gpg->addencryptkey($encrypt['fingerprint']);

        return $gpg->encryptAndSign($plainMessage);
    }

    protected function decryptMessage(string $encryptedMessage)
    {
        $gpg = $this->newCryptGPG();

        $signKey = $gpg->importKey(file_get_contents(storage_path('dbs.key')));

        $gpg->addSignKey($signKey['fingerprint']);

        $decrypt = $gpg->importKey(file_get_contents(storage_path('private.key')));

        $gpg->addDecryptKey($decrypt['fingerprint']);

        $content = $gpg->decryptAndVerify($encryptedMessage);

        return json_decode($content['data'], true);
    }
}
