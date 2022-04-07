<?php

namespace HitPay\DBS\Fast;

use App\Business\Transfer;
use Exception;
use HitPay\DBS\Base;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;

class GPC extends Base
{
    private $reference;

    private $referenceType;

    private $directDebitAuthorizationReference;

    private $transferShortCode;

    /**
     * Requires input.
     *
     * @var string|null
     */
    private $receiverName;

    /**
     * Requires input.
     *
     * @var string|null
     */
    private $receiverAccountNumber;

    /**
     * Requires input.
     *
     * @var string|null
     */
    private $receiverBankSwiftCode;

    /**
     * Requires input.
     *
     * @var string|null
     */
    private $receiverEmail;

    /**
     * Requires input.
     *
     * @var string|null
     */
    private $businessName;

    /**
     * Requires input.
     *
     * @var string|null
     */
    private $amount;

    const ONE_TIME_CHARGE = 'OT';

    const RECURRING_PAYMENT_CHARGE = 'RP';

    public function __construct(
        string $reference, string $directDebitAuthorizationReference,
        string $referenceType = self::RECURRING_PAYMENT_CHARGE
    ) {
        parent::__construct();

        if ($this->isProduction) {
            $this->baseUrl = 'https://enterprise-api.dbs.com/api/sg/fast/v4/payment/transaction';
        } else {
            $this->baseUrl = 'https://testcld-enterprise-api.dbs.com/api/sg/fast/v4/payment/transaction';
        }

        if (!Str::isUuid($reference)) {
            throw new Exception('The reference must be a UUID.');
        } elseif (!preg_match('/^[a-zA-Z0-9\/\-?:().,\'+ ]{1,35}$/', $directDebitAuthorizationReference)) {
            throw new Exception('The format of direct debit authorization reference is unacceptable.');
        } elseif (!in_array($referenceType, [self::ONE_TIME_CHARGE, self::RECURRING_PAYMENT_CHARGE])) {
            throw new Exception('The reference type is unacceptable.');
        }

        $this->reference = $reference;
        $this->directDebitAuthorizationReference = $directDebitAuthorizationReference;
        $this->referenceType = $referenceType;
        $this->transferShortCode = $this->referenceType.'-'.str_replace('-', '', $this->reference);

        $this->logPath = 'collection'.DIRECTORY_SEPARATOR;
        $this->logPath .= $this->reference.'-'.Facades\Date::now()->toDateTimeString().'.txt';
    }

    public static function new(
        string $reference, string $directDebitAuthorizationReference,
        string $referenceType = self::RECURRING_PAYMENT_CHARGE
    ) {
        return new static($reference, $directDebitAuthorizationReference, $referenceType);
    }

    public function getReceiverName() : ?string
    {
        return $this->receiverName;
    }

    public function setReceiverName(string $receiverName) : self
    {
        $receiverName = Str::limit(preg_replace("/[^a-zA-Z0-9 ]+/", '', $receiverName), 140, '');

        if (Str::length($receiverName) <= 0) {
            throw new Exception('The business name must contain at least 1 alphanumeric character.');
        }

        $this->receiverName = $receiverName;

        return $this;
    }

    public function getReceiverAccountNumber() : ?string
    {
        return $this->receiverAccountNumber;
    }

    public function setReceiverAccountNumber(string $receiverAccountNumber) : self
    {
        if (!preg_match('/^[0-9]{1,34}$/', $receiverAccountNumber)) {
            throw new Exception('The account number must be number and have a maximum length of 34 characters.');
        }

        $this->receiverAccountNumber = $receiverAccountNumber;

        return $this;
    }

    public function getReceiverBankSwiftCode() : ?string
    {
        return $this->receiverBankSwiftCode;
    }

    public function setReceiverBankSwiftCode(string $receiverBankSwiftCode) : self
    {
        if (!key_exists($receiverBankSwiftCode, Transfer::$availableBankSwiftCodes)) {
            throw new Exception('The bank swift code is not accepted.');
        }

        $this->receiverBankSwiftCode = $receiverBankSwiftCode;

        return $this;
    }

    public function getBusinessName() : ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(string $businessName) : self
    {
        $businessName = trim_all(preg_replace("/[^A-Za-z0-9 ]/", '', $businessName));

        if (Str::length($businessName) <= 0) {
            throw new Exception('The business name must contain at least 1 alphanumeric character.');
        }

        $this->businessName = $businessName;

        return $this;
    }

    public function getReceiverEmail() : ?string
    {
        return $this->receiverEmail;
    }

    public function setReceiverEmail(?string $receiverEmail) : self
    {
        if (is_string($receiverEmail)) {
            if (!filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('The receiver email must be a valid email.');
            } elseif (Str::length($receiverEmail) > 75) {
                throw new Exception('The receiver email must be less than 75 characters.');
            }
        }

        $this->receiverEmail = $receiverEmail;

        return $this;
    }

    public function getAmount() : ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount) : self
    {
        if ($amount <= 0) {
            throw new Exception('The amount must be greater than zero.');
        } elseif (strlen(substr(strrchr($amount, '.'), 1)) > 2) {
            throw new Exception('The `amount` can\'t have more than 2 decimals.');
        }

        $this->amount = $amount;

        return $this;
    }

    public function process() : self
    {
        // Pre-check

        if ($this->getBusinessName() === null) {
            throw new Exception('The `businessName` can\'t be null.');
        } elseif ($this->getAmount() === null) {
            throw new Exception('The `amount` can\'t be null.');
        } elseif ($this->getReceiverName() === null) {
            throw new Exception('The `receiverName` can\'t be null.');
        } elseif ($this->getReceiverBankSwiftCode() === null) {
            throw new Exception('The `receiverBankSwiftCode` can\'t be null.');
        } elseif ($this->getReceiverAccountNumber() === null) {
            throw new Exception('The `receiverAccountNumber` can\'t be null.');
        }

        ////////////
        // Header //
        ////////////

        $body['header'] = [
            'msgId' => $this->messageId,
            'orgId' => $this->organizationID,
            'timeStamp' => substr(str_pad($this->datetime->format('Y-m-d\Th:m:s.u'), 23, '0'), 0, 23),
        ];

        //////////////////////
        // Transaction Info //
        //////////////////////

        $txnInfo['customerReference'] = $this->transferShortCode; // Maximum 35 characters, auto generated
        $txnInfo['paymentReference'] = ''; // Maximum 35 characters, optional. TODO - Please add for GPC
        $txnInfo['txnType'] = 'GPC';
        $txnInfo['txnDate'] = $this->datetime->toDateString();
        $txnInfo['txnCcy'] = 'SGD';
        $txnInfo['txnAmount'] = $this->getAmount();
        $txnInfo['purposeOfPayment'] = 'OTHR';

        // Sender Party

        $txnInfo['senderParty'] = [
            'name' => Str::limit($this->corporateName, 140, ''), // Maximum 140 characters
            'accountNo' => $this->isProduction ? $this->corporateAccountNumber : '7053050009',
            'swiftBic' => $this->mapBankSwiftCode('DBSSSGSGXXX'),
            'bankCtryCode' => 'SG',
            'mandateId' => $this->directDebitAuthorizationReference,
        ];

        // Receiving Party

        $txnInfo['receivingParty'] = [
            'name' => $this->getReceiverName(),
            'accountNo' => $this->getReceiverAccountNumber(),
            'swiftBic' => $this->mapBankSwiftCode($this->getReceiverBankSwiftCode()),
            'bankCtryCode' => 'SG',
        ];

        // Advise Delivery

        if ($this->getReceiverEmail()) {
            $txnInfo['adviseDelivery'] = [
                'mode' => 'EMAL',
                'emails' => [['email' => $this->getReceiverEmail()]],
            ];
        }

        // Transaction Details

        // todo maybe not business name only, also detail;
        $detailTitle = Str::limit($this->getBusinessName().' via HitPay', 140, '');

        $txnInfo['rmtInf'] = [
            'paymentDetails' => [['paymentDetail' => $detailTitle]],
            'clientReferences' => [['clientReference' => $detailTitle]],
            'invoiceDetails' => [['invoice' => '']], // TODO We can set the Charge ID here?
        ];

        $body['txnInfo'] = $txnInfo;

        $this->requestBody = $body;

        $this->sendRequest();

        // [
        //     [
        //         "header" => [
        //         "msgId" => "20210106b28d4e50983c146dec59def0",
        //         "timeStamp" => "2021-01-06T23:25:58.603",
        //     ],
        //     "txnResponse" => [
        //         "customerReference" => "OT-9b68ac37db8c410bb2091c93948d8f84",
        //         "paymentReference" => "",
        //         "txnRefId" => "IRGPCSG060121A0000155",
        //         "bankReference" => "IRGPCSG060121A0000155",
        //         "txnType" => "GPC",
        //         "txnStatus" => "ACTC",
        //         "txnRejectCode" => "",
        //         "txnStatusDescription" => "Success",
        //         "txnSettlementAmt" => "100.41",
        //         "txnSettlementDt" => "2021-01-06T23:25:58.423",
        //     ],
        // ]

        return $this;
    }
}
