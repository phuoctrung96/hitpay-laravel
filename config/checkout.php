<?php
return [
  'umamiAppId' => env('UMAMI_APP_ID', ''),
  'umamiStoreFrontId' => env('UMAMI_STORE_FRONT_ID', ''),
  'umamiUrl' => env('UMAMI_URL', ''),
  'cashbackFor' => env('CHECKOUT_CASHBACK_FOR', ''),
  'cashbackAmount' => env('CHECKOUT_CASHBACK_AMOUNT', ''),
  'allowDeepLinkPanel' => env('CHECKOUT_ALLOW_DEEPLINK', false),
]
?>