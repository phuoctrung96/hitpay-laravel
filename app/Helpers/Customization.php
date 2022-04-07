<?php

namespace App\Helpers;

use App\Business\CheckoutCustomisation;
use App\Enumerations\Business\PaymentMethodType;

/**
 * Class Customisation
 * @package App\Helpers
 */
class Customization
{
    public static function replaceOldGrabPay($business_id) {
      $customisationOptions = CheckoutCustomisation::where([
        'business_id' => $business_id
      ])->first();

      if ($customisationOptions instanceof CheckoutCustomisation) {
        // Payment order
        if (isset($customisationOptions->payment_order)) {
          $customisationOptions->payment_order = json_encode(self::replaceGrabPayInArray(json_decode($customisationOptions->payment_order)));
        }

        // Method rules
        if (isset($customisationOptions->method_rules)) {
          $method_rules = json_decode($customisationOptions->method_rules);

          if (isset($method_rules->amount)) {
            foreach ($method_rules->amount as $value) {
              $value->methods = self::replaceGrabPayInArray($value->methods);
            }                    
          }

          if (isset($method_rules->device)) {
            foreach ($method_rules->device as $value) {
              $value->methods = self::replaceGrabPayInArray($value->methods);
            }
          }

          $customisationOptions->method_rules = json_encode($method_rules);
        }

        $customisationOptions->save();
      }
    }

    static function replaceGrabPayInArray ($array) {
      return array_values(array_unique(array_map(function ($item) {
        return $item === PaymentMethodType::GRABPAY ? PaymentMethodType::GRABPAY_DIRECT : $item;
      }, $array)));
    }
}
