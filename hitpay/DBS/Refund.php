<?php

namespace HitPay\DBS;

use App\Enumerations\CurrencyCode;
use Exception;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;

class Refund extends Base
{
    private $refundReference;

    private $originalTransactionReferenceByBank;

    private $originalTransactionReference;

    private $originalAmount;

    private $originalCurrency;

    private $refundableAmount;

    private $refundAmount;

    private $message;

    /**
     * Refund constructor.
     *
     * @param string $refundReference
     * @param string $originalTransactionReferenceByBank
     * @param string $originalTransactionReference
     *
     * @throws \Exception
     */
    public function __construct(
        string $refundReference, string $originalTransactionReferenceByBank, string $originalTransactionReference
    ) {
        parent::__construct();

        if ($this->isProduction) {
            $this->baseUrl = 'https://enterprise-api.dbs.com/api/sg/fast/v4/refund/transaction';
        } else {
            $this->baseUrl = 'https://testcld-enterprise-api.dbs.com/api/sg/fast/v4/refund/transaction';
        }

        if (!Str::isUuid($refundReference)) {
            throw new Exception('The reference must be a UUID.');
        }

        $this->refundReference = $refundReference;
        $this->originalTransactionReferenceByBank = $originalTransactionReferenceByBank;
        $this->originalTransactionReference = $originalTransactionReference;
        $this->shortCode = str_replace('-', '', $this->refundReference);

        $this->logPath = 'refund'.DIRECTORY_SEPARATOR;
        $this->logPath .= $this->refundReference.'-'.Facades\Date::now()->toDateTimeString().'.txt';
    }

    /**
     * New instance helper.
     *
     * @param string $refundReference
     * @param string $originalTransactionReferenceByBank
     * @param string $originalTransactionReference
     *
     * @return static
     * @throws \Exception
     */
    public static function new(
        string $refundReference, string $originalTransactionReferenceByBank, string $originalTransactionReference
    ) {
        return new static($refundReference, $originalTransactionReferenceByBank, $originalTransactionReference);
    }

    /**
     * @return string|null
     */
    public function getOriginalCurrency() : ?string
    {
        return $this->originalCurrency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     * @throws \Exception
     */
    public function setOriginalCurrency(string $currency) : self
    {
        $currency = strtoupper($currency);

        if ($currency !== strtoupper(CurrencyCode::SGD)) {
            throw new Exception('The current refund method supports only SGD.');
        }

        $this->originalCurrency = $currency;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getOriginalAmount() : ?float
    {
        return $this->originalAmount;
    }

    /**
     * @param float $amount
     *
     * @return $this
     * @throws \Exception
     */
    public function setOriginalAmount(float $amount) : self
    {
        if ($amount <= 0) {
            throw new Exception('The original amount must be greater than zero.');
        } elseif (strlen(substr(strrchr($amount, '.'), 1)) > 2) {
            throw new Exception('The original amount can\'t have more than 2 decimals.');
        }

        $this->originalAmount = $amount;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRefundableAmount() : ?float
    {
        return $this->refundableAmount;
    }

    /**
     * @param float $amount
     *
     * @return $this
     * @throws \Exception
     */
    public function setRefundableAmount(float $amount) : self
    {
        if ($this->getOriginalAmount() === null) {
            throw new Exception('The original amount must be set before refundable amount.');
        } elseif ($amount < 0) {
            throw new Exception('The refundable amount must be greater than zero.');
        } elseif (strlen(substr(strrchr($amount, '.'), 1)) > 2) {
            throw new Exception('The refundable amount can\'t have more than 2 decimals.');
        } elseif ($amount > $this->getOriginalAmount()) {
            throw new Exception('The refundable amount must be lesser than the refundable amount.');
        }

        $this->refundableAmount = $amount;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRefundAmount() : ?float
    {
        return $this->refundAmount;
    }

    /**
     * @param float $amount
     *
     * @return $this
     * @throws \Exception
     */
    public function setRefundAmount(float $amount) : self
    {
        if ($this->getRefundableAmount() === null) {
            throw new Exception('The refundable amount must be set before refund amount.');
        } elseif ($amount <= 0) {
            throw new Exception('The refund amount must be greater than zero.');
        } elseif (strlen(substr(strrchr($amount, '.'), 1)) > 2) {
            throw new Exception('The refund amount can\'t have more than 2 decimals.');
        } elseif ($amount > $this->getRefundableAmount()) {
            throw new Exception('The refund amount must be lesser than the original amount.');
        }

        $this->refundAmount = $amount;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage() : ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     *
     * @return $this
     */
    public function setMessage(?string $message) : self
    {
        $message = Str::limit(trim_all(preg_replace("/[^A-Za-z0-9 ]/", '', $message)), 97, '');

        if (Str::length($message) === 0) {
            $message = null;
        }

        $this->message = $message;

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function process() : self
    {
        if ($this->getOriginalAmount() === null) {
            throw new Exception('The original amount can\'t be null.');
        } elseif ($this->getRefundAmount() === null) {
            throw new Exception('The refund amount can\'t be null.');
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

        $txnInfo['txnType'] = 'RFD';
        $txnInfo['refundRef'] = $this->shortCode;
        $txnInfo['accountNo'] = $this->isProduction ? $this->corporateAccountNumber : '7053050009';
        $txnInfo['txnRefNo'] = $this->originalTransactionReferenceByBank; // Original transaction reference assigned by
        // bank. Only funds credited within 30 days prior to the day the Refund API is called are eligible for refund.
        // For SG/VN: Either txnRefNo or customerReference should be provided. Transaction will be validated against
        // the combination of values if more than one field are provided.
        $txnInfo['customerReference'] = $this->originalTransactionReference; // Original customer reference/end-to-end
        // id. Only funds credited within 30 days prior to the day the Refund API is called are eligible for refund.
        // For SG/VN: Either txnRefNo or customerReference should be provided. Transaction will be validated against
        // the combination of values if more than one field are provided.
        $txnInfo['txnAmount'] = $this->getOriginalAmount(); // Original transaction amount
        $txnInfo['txnCcy'] = $this->getOriginalCurrency(); // Original transaction currency, SG supports only SGD now.
        $txnInfo['retAmount'] = $this->getRefundAmount(); // Return/Refund credited amount
        $txnInfo['addInfo'] = $this->getMessage(); // Additional information on transactions, S97

        $body['txnInfo'] = $txnInfo;

        $this->requestBody = $body;

        $this->sendRequest();

        return $this;
    }
}
