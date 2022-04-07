<?php

namespace HitPay\Business\Charge;

use App\Business\PaymentProvider;
use App\Charge;
use App\Enumerations\CurrencyCode;
use App\Exceptions\HitPayLogicException;

final class Calculator
{
    protected $currency;

    protected $method;

    protected $type;

    protected $fixedAmountRate;

    protected $percentageRate;

    protected $channel;

    protected $paymentProvider;

    protected $amount = 0;

    const DESTINATION = 'destination';

    const DIRECT = 'direct';

    /**
     * Calculator constructor.
     *
     * @param \App\Business\PaymentProvider $paymentProvider
     * @param string $currency
     * @param string $method
     * @param string $type
     * @param int $fixedAmountRate
     * @param float $percentageRate
     * @param string|null $channel
     *
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function __construct(Charge $charge)
    {
        // $paymentProvider = PaymentProvider::where('payment_provider', $charge->payment_provider)->where('business_id');
        //
        // if ($type !== static::DESTINATION && $type !== static::DIRECT) {
        //     throw new HitPayLogicException(sprintf('The given charge type [%s] is invalid.', $type));
        //     // todo support multi currency
        // } elseif ($currency !== CurrencyCode::MYR && $currency !== CurrencyCode::SGD) {
        //     throw new HitPayLogicException(sprintf('The given currency [%s] is invalid.', $currency));
        // }
        //
        // $this->currency = $currency;
        // $this->method = $method;
        // $this->type = $type;
        // $this->fixedAmountRate = $fixedAmountRate;
        // $this->percentageRate = $percentageRate;
        // $this->channel = $channel;
        // $this->paymentProvider = $paymentProvider;
    }

    /**
     * @return string
     */
    public function currency() : string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function method() : string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function type() : string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function fixedAmountRate() : int
    {
        return $this->fixedAmountRate;
    }

    /**
     * @return int
     */
    public function fixedAmountRateFee() : int
    {
        return $this->fixedAmountRate();
    }

    /**
     * @return float
     */
    public function percentageRate() : float
    {
        return $this->percentageRate;
    }

    /**
     * @param bool $isActualValue
     *
     * @return int|float
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function percentageRateFee()
    {
        return $this->amount() * $this->percentageRate();
    }

    public function rawPercentageRateFee()
    {
        return $this->rawAmount() * $this->percentageRate();
    }

    /**
     * @return string
     */
    public function channel() : string
    {
        return $this->channel ?? 'none';
    }

    /**
     * @return \App\Business\PaymentProvider
     */
    public function paymentProvider() : PaymentProvider
    {
        return $this->paymentProvider;
    }

    /**
     * @param bool $isActualValue
     *
     * @return int|float
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function amount()
    {
        switch ($this->currency()) {

            case CurrencyCode::MYR:
            case CurrencyCode::SGD:
                return bcdiv($this->amount, 100);

            default:
                throw new HitPayLogicException(sprintf('The given currency [%s] is invalid.', $this->currency()));
        }
    }

    public function rawAmount() : int
    {
        return $this->amount;
    }

    /**
     * @param int|float $amount
     * @param bool $isActualValue
     *
     * @return $this
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function setAmount($amount) : self
    {
        switch ($this->currency()) {

            case CurrencyCode::MYR:
            case CurrencyCode::SGD:
                $amount = bcmul($amount, 100);

                break;

            default:
                throw new HitPayLogicException(sprintf('The given currency [%s] is invalid.', $this->currency()));
        }

        return $this->setRawAmount($amount);
    }

    public function setRawAmount(int $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param string $currency
     * @param bool $getActualValue
     *
     * @return int
     * @throws \App\Exceptions\HitPayLogicException
     */
    public static function getMinimumChargeableAmount(string $currency, bool $getActualValue = false) : int
    {
        switch ($currency) {

            case CurrencyCode::MYR:
                return $getActualValue ? 300 : 3;

            case CurrencyCode::SGD:
                return $getActualValue ? 100 : 1;
        }

        throw new HitPayLogicException(sprintf('The given currency [%s] is invalid.', $currency));
    }
}
