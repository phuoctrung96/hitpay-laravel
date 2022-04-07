<?php

namespace App\Enumerations\Business;

/**
 * Class ApiChannel
 * @package App\Enumerations\Business
 */
class PluginProvider
{
    public const PREFIX         = 'api_';

    public const CUSTOM         = 'api_custom';
    public const SHOPIFY        = 'shopify';
    public const WOOCOMMERCE    = 'woocommerce';
    public const PRESTASHOP     = 'api_prestashop';
    public const MAGENTO        = 'api_magento';
    public const XERO           = 'api_xero';
    public const PLATFORM       = 'api_platform';
    public const QUICKBOOKS     = 'api_quickbooks';
    public const ECWID          = 'api_ecwid';
    public const OPENCART       = 'api_opencart';
    public const LINK           = 'api_link';
    public const INVOICE        = 'invoice';
    public const APIWOOCOMMERCE = 'api_woocomm';
    public const APISHOPIFY = 'api_shopify';
    public const STORE = 'api_store';
    public const GOOGLE_FORMS = 'api_google_forms';

    public const DEFAULT_CHANNEL = self::CUSTOM;

    public const CHANNELS = [
        self::CUSTOM,
        self::SHOPIFY,
        self::WOOCOMMERCE,
        self::PRESTASHOP,
        self::MAGENTO,
        self::XERO,
        self::PLATFORM,
        self::QUICKBOOKS,
        self::ECWID,
        self::OPENCART,
        self::LINK,
        self::INVOICE,
        self::APIWOOCOMMERCE,
        self::APISHOPIFY,
        self::STORE,
        self::GOOGLE_FORMS,
    ];

    public const GATEWAY_CHANNELS = [
        self::SHOPIFY,
        self::WOOCOMMERCE,
    ];

    public const API_CHANNELS = [
        self::CUSTOM,
        self::PRESTASHOP,
        self::MAGENTO,
        self::XERO,
        self::PLATFORM,
        self::QUICKBOOKS,
        self::ECWID,
        self::OPENCART,
        self::LINK,
        self::INVOICE,
        self::APIWOOCOMMERCE,
        self::STORE,
        self::GOOGLE_FORMS,
        self::APISHOPIFY,
    ];

    /**
     * @param $gateWay
     * @return string|null
     */
    public static function getGateWayChannel($gateWay)
    {
        $gateWay = self::PREFIX . strtolower($gateWay);
        if (in_array($gateWay, self::GATEWAY_CHANNELS)) {
            return $gateWay;
        }

        return null;
    }

    /**
     * @param false $forDropDown
     * @return string[]
     */
    public static function getAll($forDropDown = false, $all = false)
    {
        if ($forDropDown) {
            $channels = [
                self::SHOPIFY                                          => 'Shopify',
                self::getProviderByChanel(self::CUSTOM)         => 'Payment Request APIs',
                self::getProviderByChanel(self::XERO)           => 'Xero',
                self::getProviderByChanel(self::PRESTASHOP)     => 'Prestashop',
                self::getProviderByChanel(self::MAGENTO)        => 'Magento',
                self::getProviderByChanel(self::ECWID)          => 'Ecwid',
                self::getProviderByChanel(self::OPENCART)       => 'OpenCart',
                self::getProviderByChanel(self::LINK)           => 'Link',
                self::INVOICE                                          => 'Invoice',
                self::getProviderByChanel(self::APIWOOCOMMERCE) => 'WooCommerce Plugin',
                self::getProviderByChanel(self::STORE) => 'HitPay Store',
                self::getProviderByChanel(self::GOOGLE_FORMS) => 'Google Forms',
            ];

            if ($all)
                $channels['all'] = 'All Channels';
            return $channels;
        }

        return [
            self::SHOPIFY                                   => 'Shopify',
            self::WOOCOMMERCE                               => 'WooCommerce',
            self::getProviderByChanel(self::XERO)    => 'Xero',
        ];
    }

    /**
     * @param $channel
     * @return string|string[]
     */
    public static function getProviderByChanel($channel)
    {
        return str_replace(self::PREFIX, '', $channel);
    }
}
