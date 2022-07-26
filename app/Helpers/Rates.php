<?php

namespace App\Helpers;

use StdClass;
use HitPay\Business\PaymentProviderUtil;

/**
 * Class Rates
 * @package App\Helpers
 */
class Rates
{
    use PaymentProviderUtil;

    public static function getRatesForCheckoutSettings(
      $business, 
      $paymentMethods,
      $chargeCurrency,
      $chargeChannel,
      $amount)
    {
      $rates = [];

      foreach ($paymentMethods as $method) {
        // Get payment provider by method
        $provider = self::getProviderForMethod($business, $method);

        if ($provider) {
          [ $fixed, $percent ] = $provider->getRateFor(
            $chargeCurrency,
            $chargeChannel,
            $method,
            null,
            null,
            $amount * 100
          );

          $total = round(($amount + ($fixed / 100)) / (1 - $percent), 2);

          $rates[$method] = [
            'fixed' => $fixed,
            'percent' => $percent,
            'adminFee' => round($total - $amount, 2),
            'total' => $total
          ];  
        }
      }

      return $rates;
    }
 
    public static function getRatesForMethod(
      $business, 
      $method,
      $chargeCurrency,
      $chargeChannel,
      $amount)
    {
      // Get payment provider by method
      $provider = self::getProviderForMethod($business, $method);

      if ($provider) {
        $adminFees = $business->customizationAdminFees();

        if (isset($adminFees->enabled) && $adminFees->enabled) {
          // If fees are not enabled for Payment Request, set them basing on customization options
          $useFeeForMethod = in_array($chargeChannel, $adminFees->channels) && property_exists($adminFees->methods, $method);
        } else {
          $useFeeForMethod = false;
        }
        
        // Admin fee
        $adminFee = $useFeeForMethod
          ? round($amount * ($adminFees->methods->$method / 100), 2)
          : 0;
        
        $total = $useFeeForMethod
          ? $amount + $adminFee
          : $amount;

        return [
          'addFee' => $useFeeForMethod,
          'adminFee' => $adminFee,
          'total' => $total,
        ];
          
      } else {
        return false;
      }
    }

    public static function getRatesForAllMethods(
      $business, 
      $paymentMethods,
      $chargeCurrency,
      $chargeChannel,
      $amount,
      $adminFeeForAll)
    {
      $rates = [];

      foreach ($paymentMethods as $method) {
        $r = self::getRatesForMethod($business, 
          $method,
          $chargeCurrency,
          $chargeChannel,
          $amount,
          $adminFeeForAll);

        if ($r) {
          $rates[$method] = $r;
        }
      }

      return $rates;
    }
}
