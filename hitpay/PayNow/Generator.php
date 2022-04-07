<?php

namespace HitPay\PayNow;

use App\Exceptions\HitPayLogicException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Generator
{
    /**
     * @var string
     */
    private static $dateFormat = 'YmdHis';

    /**
     * This is the Unique Entity Number (UEN) for HitPay account.
     *
     * @var string
     */
    private $proxyValue = '201605883W';

    /**
     * @var \Carbon\Carbon|null
     */
    private $expiryAt;

    /**
     * @var string|null
     */
    private $merchantName;

    /**
     * @var float|null
     */
    private $amount;

    /**
     * @var string|null
     */
    private $reference;

    /**
     * Generator constructor.
     *
     * @param  string|null  $request
     */
    public function __construct(string $request = null)
    {
        if (is_null($request)) {
            $this->reference = 'DICNP'.bcmul(microtime(true), '10000').strtoupper(Str::random(6));
        } elseif ($request === 'top-up-wallet') {
            $this->reference = 'DICNT'.bcmul(microtime(true), '10000').strtoupper(Str::random(6));
        } else {
            throw new Exception('The request is invalid.');
        }
    }

    /**
     * @param  string|null  $request
     *
     * @return static
     */
    public static function new(string $request = null)
    {
        return new static($request);
    }

    /**
     * @return \Illuminate\Support\Carbon
     */
    public function getExpiryAt() : Carbon
    {
        return Date::create(static::$dateFormat, $this->expiryAt);
    }

    /**
     * @param \Illuminate\Support\Carbon $expiryAt
     *
     * @return \HitPay\PayNow\Generator
     */
    public function setExpiryAt(Carbon $expiryAt) : Generator
    {
        $this->expiryAt = $expiryAt->format(static::$dateFormat);

        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantName() : string
    {
        return $this->merchantName;
    }

    /**
     * @param string $merchantName
     *
     * @return Generator
     */
    public function setMerchantName(string $merchantName) : Generator
    {
        $this->merchantName = Str::limit($merchantName, 25, '');

        return $this;
    }

    /**
     * @return int
     */
    public function getAmount() : int
    {
        return (int) bcmul((string) $this->amount, '100');
    }

    /**
     * @param int $amount
     *
     * @return Generator
     */
    public function setAmount(int $amount) : Generator
    {
        $this->amount = bcdiv((string) $amount, '100', 2);

        return $this;
    }

    /**
     * @return string
     */
    public function getReference() : string
    {
        return $this->reference;
    }

    /**
     * @return string
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function generate()
    {
        switch (null) {

            case $this->expiryAt:
            case $this->merchantName:
            case $this->amount:

                throw new HitPayLogicException('The required information is incomplete.');
        }

        if (!Config::get('services.dbs.paynow.status', true)) {
            $startDateTime = Config::get('services.dbs.paynow.status_from');
            $startDateTime = $startDateTime ? Date::createFromFormat('Y-m-d H:i:s', $startDateTime) : false;

            $endDateTime = Config::get('services.dbs.paynow.status_to');
            $endDateTime = $endDateTime ? Date::createFromFormat('Y-m-d H:i:s', $endDateTime) : false;

            if (!$startDateTime && !$endDateTime) {
                return 'service_unavailable';
            }

            $now = Date::now();

            if ($startDateTime && $endDateTime) {
                if ($now->between($startDateTime, $endDateTime)) {
                    return 'service_unavailable';
                }
            } elseif ($startDateTime) {
                if ($now->isAfter($startDateTime)) {
                    return 'service_unavailable';
                }
            } elseif ($endDateTime) {
                if ($now->isBefore($endDateTime)) {
                    return 'service_unavailable';
                }
            }
        }

        if (!App::environment('production') && Config::get('app.paynow_mock')) {
            return URL::route('paynow.mock.page', [
                'hash' => Crypt::encrypt([
                    'reference' => $this->reference,
                    'amount' => $this->amount,
                    'expiry_at' => $this->expiryAt,
                ]),
            ]);
        }

        // Payload Format Indicator - Code 00; Length 02; Value 01;
        // The Payload Format Indicator shall contain a value of "01". All other values are RFU.
        $data['00'] = '000201';
        // Point of Initiation Method - Code 01; Length 02: Value 11/12;
        // The value of "11" should be used when the same QR Code is shown for more than one transaction and the value
        // of “12” should be used when a new QR Code is shown for each transaction.
        $data['01'] = '010212';

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Code 26 starts…                                                                                            //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // Merchant Account Information - Code 26; Length to be calculated, max 99; Value to be inserted.

        // Code 26 Globally Unique Identifier - Code 00; Length 09; Value SG.PAYNOW;
        $dataFor26['00'] = '0009SG.PAYNOW';
        // Code 26 Proxy Type - Code 01; Length 01; Value 0/2;
        // The value of "0" should be use when the proxy type is a mobile number or the value of "2" should be used when
        // the type is Unique Entity Number (UEN).
        $dataFor26['01'] = '01012';
        // Code 26 Proxy Value - Code 02; Length to be calculated, max 16; Value to be inserted, alphanumeric special.
        $dataFor26['02'] = '02'.$this->transform($this->proxyValue);
        // Code 26 Editable Transaction Amount Indicator - Code 03; Length 01; Value 0;
        // The value of "0" should be use when the amount is not editable or the value of "1" should be used when the
        // amount is editable. We hardcoded here because we are not allowing customer to change the amount.
        $dataFor26['03'] = '03010';
        // Code 26 Expiry Date And Time - Code 04; Length 08/14; Value to be inserted, format Ymd/YmdHis.
        // If expiry date and time is not provided, no validation required.
        $dataFor26['04'] = '04'.$this->transform($this->expiryAt);

        $dataFor26 = implode('', $dataFor26);

        $data['26'] = '26'.$this->transform($dataFor26);

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Code 26 ends…                                                                                              //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // Merchant Category Code - Code 52; Length 04; Value 0000;
        // The value must be a merchant category code that conforms to ISO 18245. If this is not utilised by a payment
        // scheme, "0000" is to be added in.
        $data['52'] = '52040000';
        // Transaction Currency - Code 53; Length 03; Value 702;
        // The value must be a currency code that conforms to ISO 4127. We hardcoded here since this is for DBS PayNow
        // using Singapore Dollar only and DBS requires us to do so.
        $data['53'] = '5303702';
        // Transaction Amount - Code 54; Length to be calculated, max 13; Value to be inserted, alphanumeric special.
        $data['54'] = '54'.$this->transform($this->amount);
        // Country Code - Code 58; Length 02; Value SG.
        // The value must be a country code that conforms to ISO 3166. We hardcoded here since this is for DBS PayNow in
        // Singapore only and DBS requires us to do so.
        $data['58'] = '5802SG';
        // Merchant Name - Code 59; Length to be calculated, max 25; Value to be inserted, alphanumeric special.
        $data['59'] = '59'.$this->transform($this->merchantName);
        // Merchant City - Code 60; Length 09; Value Singapore;
        // We hardcoded here since this is for DBS PayNow only and DBS requires us to do so.
        $data['60'] = '6009Singapore';

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Code 62 starts…                                                                                            //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // Additional Data Field Template - Code 62; Length to be calculated, max 99; Value to be inserted.

        // Code 62 Bill Number - Code 01; Length to be calculated, max 25; Value to be inserted, alphanumeric special.
        $dataFor62['01'] = '01'.$this->transform($this->reference);

        $dataFor62 = implode('', $dataFor62);

        $data['62'] = '62'.$this->transform($dataFor62);

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Code 62 ends…                                                                                              //
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // Cyclic Redundancy Check (CRC) - Code 63; Length 04; Value to be calculated.
        $data['63'] = '6304';

        $data['63'] = $data['63'].bin2hex(CRC16CCITT::generate(implode('', $data)));

        return implode('', $data);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function transform(string $value)
    {
        return (str_pad(strlen($value), 2, '0', STR_PAD_LEFT)).$value;
    }
}
