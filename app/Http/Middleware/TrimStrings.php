<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',

        // Shopify fields
        'x_account_id',
        'x_amount',
        'x_currency',
        'x_reference',
        'x_shopify_order_id',
        'x_shop_name',
        'x_shop_country',
        'x_customer_first_name',
        'x_customer_last_name',
        'x_customer_email',
        'x_customer_phone',
        'x_customer_billing_country',
        'x_customer_billing_city',
        'x_customer_billing_company',
        'x_customer_billing_address1',
        'x_customer_billing_address2',
        'x_customer_billing_state',
        'x_customer_billing_zip',
        'x_customer_billing_phone',
        'x_customer_shipping_country',
        'x_customer_shipping_first_name',
        'x_customer_shipping_last_name',
        'x_customer_shipping_city',
        'x_customer_shipping_company',
        'x_customer_shipping_address1',
        'x_customer_shipping_address2',
        'x_customer_shipping_state',
        'x_customer_shipping_zip',
        'x_customer_shipping_phone',
        'x_description',
        'x_invoice',
        'x_url_callback',
        'x_url_cancel',
        'x_url_complete',
        'x_test',
        'x_shop_url',
    ];
}
