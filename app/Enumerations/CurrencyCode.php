<?php

namespace App\Enumerations;

use Stripe\Stripe;

/**
 * Class CurrencyCode
 * @package App\Enumerations
 */
class CurrencyCode extends Enumeration
{
    public const USD = 'usd';
    public const AED = 'aed';
    public const AFN = 'afn';
    public const ALL = 'amd';
    public const AMD = 'amd';
    public const ANG = 'ang';
    public const AOA = 'aoa';
    public const ARS = 'ars';
    public const AUD = 'aud';
    public const AWG = 'awg';
    public const AZN = 'azn';
    public const BAM = 'bam';
    public const BBD = 'bbd';
    public const BDT = 'bdt';
    public const BGN = 'bgn';
    public const BIF = 'bif';
    public const BMD = 'bmd';
    public const BND = 'bnd';
    public const BOB = 'bob';
    public const BRL = 'brl';
    public const BSD = 'bsd';
    public const BWP = 'bwp';
    public const BZD = 'bzd';
    public const CAD = 'cad';
    public const CDF = 'cdf';
    public const CHF = 'chf';
    public const CLP = 'clp';
    public const CNY = 'cny';
    public const COP = 'cop';
    public const CRC = 'crc';
    public const CVE = 'cve';
    public const CZK = 'czk';
    public const DJF = 'djf';
    public const DKK = 'dkk';
    public const DOP = 'dop';
    public const DZD = 'dzd';
    public const EGP = 'egp';
    public const ETB = 'etb';
    public const EUR = 'eur';
    public const FJD = 'fjd';
    public const FKP = 'fkp';
    public const GBP = 'gbp';
    public const GEL = 'gel';
    public const GIP = 'gip';
    public const GMD = 'gmd';
    public const GNF = 'gnf';
    public const GTQ = 'gtq';
    public const GYD = 'gyd';
    public const HKD = 'hkd';
    public const HNL = 'hnl';
    public const HRK = 'hrk';
    public const HTG = 'htg';
    public const HUF = 'huf';
    public const IDR = 'idr';
    public const ILS = 'ils';
    public const INR = 'inr';
    public const ISK = 'isk';
    public const JMD = 'jmd';
    public const JPY = 'jpy';
    public const KES = 'kes';
    public const KGS = 'kgs';
    public const KHR = 'khr';
    public const KMF = 'kmf';
    public const KRW = 'krw';
    public const KYD = 'kyd';
    public const KZT = 'kzt';
    public const LAK = 'lak';
    public const LBP = 'lbp';
    public const LKR = 'lkr';
    public const LRD = 'lrd';
    public const LSL = 'lsl';
    public const MAD = 'mad';
    public const MDL = 'mdl';
    public const MGA = 'mga';
    public const MKD = 'mkd';
    public const MMK = 'mmk';
    public const MNT = 'mnt';
    public const MOP = 'mop';
    public const MRO = 'mro';
    public const MUR = 'mur';
    public const MVR = 'mvr';
    public const MWK = 'mwk';
    public const MXN = 'mxn';
    public const MYR = 'myr';
    public const MZN = 'mzn';
    public const NAD = 'nad';
    public const NGN = 'ngn';
    public const NIO = 'nio';
    public const NOK = 'nok';
    public const NPR = 'npr';
    public const NZD = 'nzd';
    public const PAB = 'pab';
    public const PEN = 'pen';
    public const PGK = 'pgk';
    public const PHP = 'php';
    public const PKR = 'pkr';
    public const PLN = 'pln';
    public const PYG = 'pyg';
    public const QAR = 'qar';
    public const RON = 'ron';
    public const RSD = 'rsd';
    public const RUB = 'rub';
    public const RWF = 'rwf';
    public const SAR = 'sar';
    public const SBD = 'sbd';
    public const SCR = 'scr';
    public const SEK = 'sek';
    public const SGD = 'sgd';
    public const SHP = 'shp';
    public const SLL = 'sll';
    public const SOS = 'sos';
    public const SRD = 'srd';
    public const STD = 'std';
    public const SZL = 'szl';
    public const THB = 'thb';
    public const TJS = 'tjs';
    public const TOP = 'top';
    public const TRY = 'try';
    public const TTD = 'ttd';
    public const TWD = 'twd';
    public const TZS = 'tzs';
    public const UAH = 'uah';
    public const UGX = 'ugx';
    public const UYU = 'uyu';
    public const UZS = 'uzs';
    public const VND = 'vnd';
    public const VUV = 'vuv';
    public const WST = 'wst';
    public const XAF = 'xaf';
    public const XCD = 'xcd';
    public const XOF = 'xof';
    public const XPF = 'xpf';
    public const YER = 'yer';
    public const ZAR = 'zar';
    public const ZMW = 'zmw';

    public const ZERO_DECIMAL_CURRENCIES = [
        self::BIF,
        self::CLP,
        self::DJF,
        self::GNF,
        self::JPY,
        self::KMF,
        self::KRW,
        self::MGA,
        self::PYG,
        self::RWF,
        self::UGX,
        self::VND,
        self::VUV,
        self::XAF,
        self::XOF,
        self::XPF
    ];

    public const CURRENCY_SYMBOLS  = [
        self::EUR => '€',
        self::USD => '$',
        self::SGD => '$',
        self::MYR => 'RM',
        self::AUD => '$',
        self::JPY => '¥',
        self::GBP => '£',
    ];

    /**
     * @return string[]
     */
    public static function getList()
    {
        return [
            self::SGD => 'Singapore Dollar',
            self::USD => 'US Dollar',
            self::GBP => 'Pound Sterling',
            self::AUD => 'Australian Dollar',
            self::EUR => 'Euro',
            self::JPY => 'Yen',
        ];
    }

    /**
     * Determine if currency is zero-decimal.
     *
     * @param string $value
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function isZeroDecimal(string $value)
    {
        return in_array($value, self::ZERO_DECIMAL_CURRENCIES);
    }

    /**
     * Determine if currency is normal.
     *
     * @param string $value
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function isNormal(string $value)
    {
        return !in_array($value, self::ZERO_DECIMAL_CURRENCIES);
    }
}
