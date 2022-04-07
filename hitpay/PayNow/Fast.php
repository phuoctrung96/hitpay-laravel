<?php

namespace HitPay\PayNow;

use App\Business\Wallet\Transaction;
use Crypt_GPG;
use Crypt_GPG_Exception;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Fast
{
    /**
     * @var string
     */
    private $organizationID = 'BEACHUS3';

    /**
     * @var string
     */
    private $corporateName = 'HITPAY PAYMENT SOLUTIONS PTE LTD';

    /**
     * @var string
     */
    private $corporateAccountNumber = '0720172418';

    /**
     * @var string
     */
    private $receiverName;

    /**
     * @var string
     */
    private $receiverAccountNumber;

    /**
     * @var string
     */
    private $receiverBankSwiftCode;

    /**
     * @var string
     */
    private $businessName;

    /**
     * @var string
     */
    private $receiverEmail;

    /**
     * @var string
     */
    private $transferShortCode;

    /**
     * @var array|\App\Business\Charge[]
     */
    private $transactionDetails = [];

    /**
     * @var float
     */
    private $amount; // /^\d[9]\.\d[2]$/

    /**
     * @var \Illuminate\Support\Carbon
     */
    private $datetime;

    /**
     * @var string
     */
    private $messageId;

    /**
     * @var array
     */
    private $errorMessages = [];

    private $cutOff = true;
    private $requestBody;

    private $isCommission;

    private $response;
    private $transferId;
    private $transactionType;
    private string $filename;

    /**
     * Fast constructor.
     *
     * @param string $reference
     * @param string $transactionType
     * @param bool $isCommission
     */
    public function __construct(
        string $reference, string $transactionType = 'GPP', bool $isCommission = false, ?int $counter = null
    ) {
        if (is_int($counter) && $counter > 99) {
            throw new Exception("The counter can't be more than 99, {$counter} given.");
        }

        $this->datetime = Date::now();
        $this->transferId = $reference;
        $this->transactionType = $transactionType;
        $this->isCommission = $isCommission;
        $this->cutOff = Config::get('services.dbs.cutoff');

        $uuid = Str::orderedUuid()->toString();
        $uuidArray = explode('-', $uuid);

        array_shift($uuidArray);

        $this->messageId = $this->datetime->format('Ymd').implode('', $uuidArray); // TODO KIV It gave RJCT response sometimes

        $transferShortCode = str_replace('-', '', $this->transferId);

        if (is_int($counter)) {
            $transferShortCode .= '-'.str_pad($counter, 2, '0', STR_PAD_LEFT);
        }

        $this->transferShortCode = $transferShortCode;
        $this->filename = 'fast-payment'.DIRECTORY_SEPARATOR.$this->transferId.'-'.Date::now()->toDateTimeString().'.txt';
    }

    /**
     * @param string $reference
     * @param string $transactionType
     *
     * @return static
     */
    public static function new(string $reference, string $transactionType = 'GPP')
    {
        return new static($reference, $transactionType);
    }

    /**
     * @param  string  $reference
     * @param  int|null  $counter
     *
     * @return static
     */
    public static function transfer(string $reference, ?int $counter = null) : self
    {
        return new static($reference, 'GPP', false, $counter);
    }

    /**
     * @return string
     */
    public function getReceiverName() : ?string
    {
        return $this->receiverName;
    }

    /**
     * @param string $receiverName
     *
     * @return Fast
     */
    public function setReceiverName(string $receiverName) : Fast
    {
        $this->receiverName = preg_replace("/[^a-zA-Z0-9]+/", '', $receiverName);

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverAccountNumber() : ?string
    {
        return $this->receiverAccountNumber;
    }

    /**
     * @param string $receiverAccountNumber
     *
     * @return Fast
     */
    public function setReceiverAccountNumber(string $receiverAccountNumber) : Fast
    {
        $this->receiverAccountNumber = $receiverAccountNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverBankSwiftCode() : ?string
    {
        return $this->receiverBankSwiftCode;
    }

    /**
     * @param string $receiverBankSwiftCode
     *
     * @return Fast
     */
    public function setReceiverBankSwiftCode(string $receiverBankSwiftCode) : Fast
    {
        $this->receiverBankSwiftCode = $receiverBankSwiftCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getBusinessName() : ?string
    {
        return $this->businessName;
    }

    /**
     * @param string $businessName
     *
     * @return Fast
     */
    public function setBusinessName(string $businessName) : Fast
    {
        $this->businessName = $businessName;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverEmail() : ?string
    {
        return $this->receiverEmail;
    }

    /**
     * @param string $receiverEmail
     *
     * @return Fast
     */
    public function setReceiverEmail(?string $receiverEmail) : Fast
    {
        $this->receiverEmail = $receiverEmail;

        return $this;
    }

    /**
     * @return \App\Business\Charge[]|array|string
     */
    public function getTransactionDetails()
    {
        return $this->transactionDetails;
    }

    /**
     * @param \App\Business\Charge[]|array|string $transactionDetails
     *
     * @return Fast
     */
    public function setTransactionDetails($transactionDetails)
    {
        $this->transactionDetails = $transactionDetails;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount() : ?float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return Fast
     */
    public function setAmount(float $amount) : Fast
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return $this|bool
     * @throws \Crypt_GPG_BadPassphraseException
     * @throws \Crypt_GPG_Exception
     * @throws \Crypt_GPG_FileException
     * @throws \Crypt_GPG_KeyNotFoundException
     * @throws \HitPay\PayNow\DecryptException
     * @throws \HitPay\PayNow\EncryptException
     * @throws \PEAR_Exception
     * @throws \ReflectionException
     */
    public function generate() : Fast
    {
        $isProduction = App::environment('production');

        // Pre-check

        $this->errorMessages = [];

        if (is_null($this->amount)) {
            $this->errorMessages['amount'] = 'The `amount` can\'t be null.';
        } elseif (!is_numeric($this->amount)) {
            $this->errorMessages['amount'] = 'The `amount` must be a valid number.';
        } elseif (strlen(substr(strrchr($this->amount, '.'), 1)) > 2) {
            $this->errorMessages['amount'] = 'The `amount` can\'t have more than 2 decimals.';
        }

        if (is_null($this->receiverName)) {
            $this->errorMessages['receiverName'] = 'The `receiverName` can\'t be null.';
        }

        if ($this->transactionType === 'GPP') {
            // if (count($this->transactionDetails) === 0) {
            //     $this->errorMessages['transactionDetails'] = 'The `transactionDetails` must have at least one line.';
            // }

            if (is_null($this->receiverBankSwiftCode)) {
                $this->errorMessages['receiverBankSwiftCode'] = 'The `receiverBankSwiftCode` can\'t be null.';
            }

            if (is_null($this->receiverAccountNumber)) {
                $this->errorMessages['receiverAccountNumber'] = 'The `receiverAccountNumber` can\'t be null.';
            }

            if (is_null($this->receiverEmail)) {
                $this->errorMessages['receiverEmail'] = 'The `receiverEmail` can\'t be null.';
            } elseif (!filter_var($this->receiverEmail, FILTER_VALIDATE_EMAIL)) {
                $this->errorMessages['receiverEmail'] = 'The `receiverEmail` is not a valid email.';
            } elseif (Str::length($this->receiverEmail) > 75) {
                $this->errorMessages['receiverEmail'] = 'The `receiverEmail` can\'t be more than 75 characters.';
            }
        }

        if (count($this->errorMessages)) {
            return $this;
        }

        ////////////
        // Header //
        ////////////

        $header['msgId'] = $this->messageId; // Maximum 35 characters, auto generated
        $header['orgId'] = $this->organizationID; // Maximum 12 characters
        $header['timeStamp'] = substr($this->datetime->format('Y-m-d\Th:m:s.u'), 0, 23);

        $body['header'] = $header;

        //////////////////////
        // Transaction Info //
        //////////////////////

        $txnInfo['customerReference'] = $this->transferShortCode; // Maximum 35 characters, auto generated
        $txnInfo['txnType'] = $this->transactionType;
        $txnInfo['txnDate'] = $this->datetime->toDateString();
        $txnInfo['txnCcy'] = 'SGD';
        $txnInfo['txnAmount'] = $this->amount;
        $txnInfo['purposeOfPayment'] = 'OTHR';

        // Sender Party

        $senderParty['name'] = Str::limit($this->corporateName, 140, ''); // Maximum 140 characters
        $senderParty['accountNo'] = $isProduction ? $this->corporateAccountNumber : '7053050009';
        $senderParty['swiftBic'] = $isProduction ? 'DBSSSGSGXXX' : 'DBSSSGS0XXX';
        $senderParty['bankCtryCode'] = 'SG';

        $txnInfo['senderParty'] = $senderParty;

        // Receiving Party

        $receivingParty['name'] = Str::limit($this->receiverName, 140, ''); // Maximum 140 characters


        if ($this->transactionType === 'GPP') {
            $receivingParty['accountNo'] = $this->receiverAccountNumber;
            $receivingParty['swiftBic'] = $isProduction
                ? $this->receiverBankSwiftCode
                : substr_replace($this->receiverBankSwiftCode, '0', 7, 1);
        } elseif ($this->transactionType === 'PPP') {
            $receivingParty['proxyType'] = 'M';
            $receivingParty['proxyValue'] = $this->receiverAccountNumber;
        }

        $receivingParty['bankCtryCode'] = 'SG';

        $txnInfo['receivingParty'] = $receivingParty;

        // Advise Delivery

        if ($this->receiverEmail && filter_var($this->receiverEmail, FILTER_VALIDATE_EMAIL) && Str::length($this->receiverEmail) <= 75) {
            $adviseDelivery['mode'] = 'EMAL';
            $adviseDelivery['emails'] = [
                [
                    'email' => $this->receiverEmail,
                ],
            ];

            $txnInfo['adviseDelivery'] = $adviseDelivery;
        }

        // Transaction Details

        if ($this->transactionType === 'GPP') {
            $businessName = trim_all(preg_replace("/[^A-Za-z0-9 ]/", "", $this->businessName));

            $detailTitle = Str::limit('HitPay Payouts - '.$businessName, 140, '');
        } else {
            $detailTitle = Str::limit('HitPay Refund - '.$this->transferShortCode, 140, '');
        }

        $rmtInf['paymentDetails'] = [
            [
                'paymentDetail' => $detailTitle,
            ],
        ];

        $rmtInf['clientReferences'] = [
            [
                'clientReference' => $detailTitle,
            ],
        ];

        if ($this->transactionType === 'GPP') {
            $i = 0;

            if (is_string($this->transactionDetails)) {
                $invoiceDetails[] = [
                    'invoice' => Str::limit($this->transactionDetails, 140, ''),
                ];
            } else {
                foreach ($this->transactionDetails as $detail) {
                    if ($i === 499) {
                        $invoiceDetails[] = [
                            'invoice' => 'And more...',
                        ];

                        break;
                    }

                    if ($this->isCommission) {
                        $invoice = 'ID: '.$detail->getKey().' - '.getFormattedAmount($detail->currency, $detail->amount)
                            .' (Commission: '.getFormattedAmount($detail->home_currency, $detail->getCommission())
                            .', REF: '.$detail->plugin_provider_reference.')';
                    } elseif ($detail instanceof Transaction) {
                        $invoice = sprintf('Transfer ID: %s - %s, %s', $detail->getKey(),
                            getFormattedAmount($txnInfo['txnCcy'], $detail->amount),
                            trim_all(preg_replace("/[^A-Za-z0-9 ]/", "", $detail->description)));
                    } else {
                        $invoice = 'ID: '.$detail->getKey().' - '.getFormattedAmount($detail->currency, $detail->amount)
                            .' (Net: '.getFormattedAmount($detail->home_currency,
                                $detail->home_currency_amount - $detail->getTotalFee()).')';

                        if ($detail->remark) {
                            $invoice .= ', '.trim_all(preg_replace("/[^A-Za-z0-9 ]/", "", $detail->remark));
                        }
                    }

                    $invoiceDetails[] = [
                        'invoice' => Str::limit($invoice, 140, ''),
                    ];

                    $i++;
                }
            }

            $rmtInf['invoiceDetails'] = $invoiceDetails;
        }

        $txnInfo['rmtInf'] = $rmtInf;

        $body['txnInfo'] = $txnInfo;

        $this->requestBody = json_encode($body);

        $retry = 0;

        do {
            try {
                $gpg = new Crypt_GPG([
                    'homedir' => '/home/ubuntu/.gnupg',
                    'digest-algo' => 'SHA256',
                    'cipher-algo' => 'AES256',
                    'compress-algo' => 'zip',
                    'debug' => Config::get('app.debug'),
                ]);

                $publicKey = $gpg->importKey(file_get_contents(storage_path('dbs.key')));
                $gpg->addEncryptKey($publicKey['fingerprint']);

                $encrypt = $gpg->importKey(file_get_contents(storage_path('private.key')));
                $gpg->addSignKey($encrypt['fingerprint']);

                $gpg->addencryptkey($encrypt['fingerprint']);

                $encryptedRequestBody = $gpg->encryptAndSign($this->requestBody);
            } catch (Crypt_GPG_Exception $exception) {
                $retry++;

                if ($retry === 3) {
                    throw new EncryptException($exception->getMessage());
                }
            }
        } while (!isset($encryptedRequestBody));

        // TODO - Keep In View - 20211010
        //   ------------------------------>>>
        //   For safe, not to do redundant payment, we don't try to call the API again if failed. Unless we have a
        //   better plan or decision in the future.
        //
        $http = new Client([
            'verify' => false,
        ]);

        if ($this->cutOff === true) {
            if ($isProduction) {
                $url = 'https://enterprise-api.dbs.com/api/sg/fast/v4/payment/transaction';
                $orgId = '8beab47a-4fd7-4c8c-ab8d-51fcbd97c1df';
            } else {
                $url = 'https://testcld-enterprise-api.dbs.com/api/sg/fast/v4/payment/transaction';
                $orgId = '31b0bd79-913d-4033-925e-d3ca62a8688d';
            }

            $headers = [
                'X-API-KEY' => $orgId,
                'X-DBS-ORG_ID' => $this->organizationID,
                // 'Content-Type' => 'application/json', // Returned in Gateway rejection response (JSON format)
                'Content-Type' => 'text/plain', // Provided/Returned in encrypted request & response
            ];
        } else {
            if ($isProduction) {
                $url = 'https://aping-ideal.dbs.com/rapid/fast/v1/payment/initiatePayment';
                $orgId = 'd2f8ea87-dc70-4c8a-add2-bd2d86af5a99';
            } else {
                $url = 'https://api-ideal-staging.dbs.com/rapid/fast/v1/payment/initiatePayment';
                $orgId = 'f41e03d7-7de4-4569-8f7c-52e60618d012';
            }

            $headers = [
                'KeyId' => $orgId,
                'ORG_ID' => $this->organizationID,
                'Content-Type' => 'application/json',
            ];
        }

        $response = $http->post($url, [
            'headers' => $headers,
            'body' => $encryptedRequestBody,
        ]);

        $responseBodyContents = $response->getBody()->getContents();

        Storage::append($this->filename, implode("\n", [
            'Request Body',
            json_encode($this->requestBody),
            '',
            'Response',
            $responseBodyContents,
        ]));

        $retry = 0;

        do {
            try {
                $gpg = new Crypt_GPG([
                    'homedir' => '/home/ubuntu/.gnupg',
                    'digest-algo' => 'SHA256',
                    'cipher-algo' => 'AES256',
                    'compress-algo' => 'zip',
                    'debug' => Config::get('app.debug'),
                ]);

                $signKey = $gpg->importKey(file_get_contents(storage_path('dbs.key')));
                $gpg->addSignKey($signKey['fingerprint']);

                $decrypt = $gpg->importKey(file_get_contents(storage_path('private.key')));
                $gpg->addDecryptKey($decrypt['fingerprint']);

                $content = $gpg->decryptAndVerify($responseBodyContents);
            } catch (Crypt_GPG_Exception $exception) {
                $retry++;

                if ($retry === 3) {
                    throw new DecryptException($exception->getMessage());
                }
            }
        } while (!isset($content));

        $this->response = json_decode($content['data'], true);

        Storage::append($this->filename, implode("\n", [
            '',
            'Decrypted Response',
            json_encode($this->getResponse(), JSON_PRETTY_PRINT),
        ]));

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequestBody()
    {
        return $this->requestBody;
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    /**
     * Get error messages.
     *
     * @return array
     */
    public function getErrorMessages() : array
    {
        return $this->errorMessages;
    }
}
