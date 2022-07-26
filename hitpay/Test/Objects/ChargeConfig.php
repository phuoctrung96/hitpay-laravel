<?php

namespace HitPay\Test\Objects;

use Exception;

class ChargeConfig
{
    protected string $method;

    protected string $currency;

    protected int $amount;

    protected $cardBrand;

    protected $cardCountry;

    protected int $expectedFixedFee;

    protected float $expectedDiscountRate;

    /**
     * ChargeConfig Constructor
     *
     * @param  string  $method
     * @param  string  $currency
     * @param  int  $amount
     * @param $cardBrand
     * @param $cardCountry
     * @param  int  $expectedFixedFee
     * @param  float  $expectedDiscountRate
     *
     * @throws \Exception
     */
    public function __construct(
        string $method,
        string $currency,
        int $amount,
        $cardBrand,
        $cardCountry,
        int $expectedFixedFee,
        float $expectedDiscountRate
    ) {
        $this->method = $method;
        $this->currency = $currency;
        $this->amount = $amount;

        if (!is_string($cardBrand) && !is_null($cardBrand)) {
            throw new Exception('Invalid type for card brand');
        }

        $this->cardBrand = $cardBrand;

        if (!is_string($cardCountry) && !is_null($cardCountry)) {
            throw new Exception('Invalid type for card country');
        }

        $this->cardCountry = $cardCountry;
        $this->expectedFixedFee = $expectedFixedFee;
        $this->expectedDiscountRate = $expectedDiscountRate;
    }

    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getCurrency() : string
    {
        return $this->currency;
    }

    /**
     * @return int
     */
    public function getAmount() : int
    {
        return $this->amount;
    }

    /**
     * @return string | null
     */
    public function getCardBrand()
    {
        return $this->cardBrand;
    }

    /**
     * @return string | null
     */
    public function getCardCountry()
    {
        return $this->cardCountry;
    }

    /**
     * @return int
     */
    public function getExpectedFixedFee() : int
    {
        return $this->expectedFixedFee;
    }

    /**
     * @return float
     */
    public function getExpectedDiscountRate() : float
    {
        return $this->expectedDiscountRate;
    }
}
