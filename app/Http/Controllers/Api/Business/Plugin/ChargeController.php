<?php

namespace App\Http\Controllers\Api\Business\Plugin;

use App\Actions\Business\Stripe\Charge\PaymentIntent\AttachPaymentMethod;
use App\Actions\Business\Stripe\Charge\PaymentIntent\Confirm;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Business\Charge;
use App\Manager\ChargeManagerInterface;
use App\Manager\CustomerManagerInterface;
use App\Manager\FactoryPaymentIntentManagerInterface;
use App\Http\Resources\Business\Charge as ChargeResource;
use App\Http\Requests\CreateChargePaymentIntentRequest;
use App\Http\Requests\CreateChargeRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\PaymentIntent as PaymentIntentResource;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Business\PaymentRequest;
use App\Business\PaymentIntent;
use HitPay\Business\PaymentProviderUtil;
use App\Helpers\Rates;
use App\Helpers\Currency;
use App\Enumerations\Business\PaymentMethodType;
use Stripe\Exception\CardException;
use HitPay\Data\FeeCalculator;

class ChargeController extends Controller
{
    use PaymentProviderUtil;

    /**
     * ChargeController constructor.
     */
    public function __construct()
    {
        //$this->middleware('auth:plugin');
    }

    /**
     * @param CreateChargeRequest $request
     * @param Business $business
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function create(CreateChargeRequest $request, Business $business, ChargeManagerInterface $chargeManager)
    {
        try {
            return Response::json($chargeManager->createRequiresPaymentMethod($business, $request->post()), 201);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param CreateChargePaymentIntentRequest $request
     * @param Business $business
     * @param Charge $charge
     * @param FactoryChargeManagerInterface $factory
     * @param CustomerManagerInterface $customerManager
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createPaymentIntent(
        CreateChargePaymentIntentRequest $request,
        Business $business,
        Charge $charge,
        FactoryPaymentIntentManagerInterface $factory,
        CustomerManagerInterface $customerManager,
        ChargeManagerInterface $chargeManager
    ) {
        Gate::inspect('operate', [$charge, $business])->authorize();

        // re-check charge info
        if ($charge->status === 'succeeded') {
          return Response::json([
            'alreadyPaid' => true,
          ]);
        } else {
          // validate method
          $validatedData = $request->validate([
            'method' => [ 'required', Rule::in(PaymentMethodType::getPaymentMethods()) ],
          ]);

          $stripePaymentIntentManager = $factory->create($validatedData['method']);

          // We try to look into database for customer, sometimes there's a collision and error thrown, if three times
          // we can't get the customer, then only we throw error.

          $tries = 0;

          do {
              try {
                  $customer = $customerManager->getFindOrCreateByEmail(
                      $business,
                      $request->input('email'),
                      $charge->paymentRequest->name ?? null,
                      $charge->paymentRequest->phone ?? null
                  );
              } catch (QueryException $e) {
                  if ($tries === 3) {
                      throw $e;
                  }

                  $tries++;
              }
          } while(!isset($customer));

          if ($request->has('description')) {
              $chargeManager->updateRemark($charge, $request->input('description'));
          }

          // amount handling
          $paymentRequest = PaymentRequest::find($charge->plugin_provider_reference);

          if ($paymentRequest) {
            $chargeUpdateData = [
              'payment_provider_charge_method' => $validatedData['method']
            ];

            $provider = Rates::getProviderForMethod($business, $validatedData['method']);

            // minimum amount handling
            $allowedCurrencies = $provider->getPaymentMethodCurrencies($validatedData['method']);
            $usedCurrencyRules = $allowedCurrencies[$charge->currency] ?? null;

            if ($usedCurrencyRules) {
                if ($paymentRequest->is_default === 1 && $request->has('amount')) {
                    $amount = $request->get('amount');
                    $amount = getRealAmountForCurrency($charge->currency, $amount);
                } else {
                    $amount = $charge->amount;
                }
              // Throw minimum amount error based on the selected currency and payment provider
              if ($amount < $usedCurrencyRules['minimum_amount']) {
                  return Response::json([
                      'error_message' => 'Transaction Failed. Minimum amount allowed to be charged is ' . getFormattedAmount($charge->currency, $usedCurrencyRules['minimum_amount']),
                  ], 400);
              }
            } else {
              // Currency not allowed for this payment provider
              Facades\Log::critical("The business (ID : {$business->getKey()}) is trying to create a charge with unsupported currency ({$charge->currency}) using {$provider->payment_provider}. Please check.");

              return Response::json([
                  'error_message' => 'Business is not configured properly, our support team has been notified.',
              ], 400);
            }


            if ($paymentRequest->is_default === 1) {
              // For default checkouts amount can be changed by user
              if ($request->has('amount')) {
                $chargeUpdateData['amount'] = getRealAmountForCurrency($charge->currency, str_replace(',', '', $request->input('amount')));

                // Default links do not support admin fees customization, so use standard fees for each method
                [ $fixed, $percent ] = $provider->getRateFor(
                  $charge->currency,
                  $paymentRequest->channel,
                  $validatedData['method'],
                  null,
                  null,
                  $chargeUpdateData['amount']
                );

                $chargeUpdateData['admin_fee'] = false;
                $chargeUpdateData['fixed_fee'] = $fixed;
                $chargeUpdateData['discount_fee_rate'] = $percent;
                $chargeUpdateData['discount_fee'] = round($chargeUpdateData['amount'] * $percent);
              }
            } else {
              $rate = Rates::getRatesForMethod(
                $business,
                $validatedData['method'],
                $charge->currency,
                $paymentRequest->channel,
                $paymentRequest->amount,
                $paymentRequest->add_admin_fee === 1
              );

              $chargeUpdateData['admin_fee'] = $rate['addFee'];

              // Total amount in charge currency
              $charge->amount = Currency::isZeroDecimal($charge->currency)
                ? $rate['total']
                : (int) bcmul($rate['total'], "100");

              $fees = FeeCalculator::forBusinessCharge($charge, $provider, $validatedData['method'])
                ->calculate()->toArray();

              $chargeUpdateData['exchange_rate'] = $fees['exchange_rate'];
              $chargeUpdateData['discount_fee_rate'] = $fees['discount_fee_rate'];

              $homeCurrencyBreakdown = $fees['breakdown']['home_currency'];

              $chargeUpdateData['fixed_fee'] = $homeCurrencyBreakdown['fixed_fee_amount'];
              $chargeUpdateData['discount_fee'] = $homeCurrencyBreakdown['discount_fee_amount'];
            }

            $charge->update($chargeUpdateData);
          } else {
            // Old scheme without Payment Request, woocommerce, shopify
            $provider = Rates::getProviderForMethod($business, $validatedData['method']);

            [ $fixed, $percent ] = $provider->getRateFor(
              $charge->currency,
              $charge->plugin_provider,
              $validatedData['method'],
              null,
              null,
              $charge->amount
            );

            $chargeUpdateData['admin_fee'] = false;
            $chargeUpdateData['fixed_fee'] = $fixed;
            $chargeUpdateData['discount_fee_rate'] = $percent;
            $chargeUpdateData['discount_fee'] = round($charge->amount * $percent);

            $charge->update($chargeUpdateData);
          }

          $chargeManager->assignCustomer($charge, $customer);

          try {
            return $stripePaymentIntentManager->create($charge, $business);
          } catch (BadRequest $exception) {
            return Response::json([
              'error_message' => $exception->getMessage(),
            ], 400);
          }
        }
    }

    /**
     * @param string $paymentIntentId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentIntentOnly(string $paymentIntentId)
    {
        $paymentIntent = Business\PaymentIntent::findOrFail($paymentIntentId);

        return Response::json([
            'charge' => [
                'status' => $paymentIntent->charge->status,
            ],
            'status' => $paymentIntent->status,
        ]);
    }

    /**
     * @param Business $business
     * @param string $paymentIntentId
     *
     * @return \App\Http\Resources\Business\PaymentIntent
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getPaymentIntent(Business $business, string $paymentIntentId)
    {
        try {
            $paymentIntent = $business->paymentIntents()->findOrFail($paymentIntentId);
            $paymentIntent->load('charge');

            return new PaymentIntentResource($paymentIntent);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Business $business
     * @param string $paymentIntentId
     * @param ChargeManagerInterface $chargeManager
     *
     * @return \App\Http\Resources\Business\PaymentIntent|\Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function capturePaymentIntent(
        Business $business, string $paymentIntentId, ChargeManagerInterface $chargeManager
    ) {
        try {
            $paymentIntent = $business->paymentIntents()->findOrFail($paymentIntentId);
            $paymentIntent = $chargeManager->captureStripePaymentIntent($business, $paymentIntent);
        } catch (Exception $e) {
            throw $e;
        }

        return Response::json($paymentIntent->toArray());
    }

    public function confirmPaymentIntent(Request $request, Business $business, string $paymentIntentId)
    {
        $paymentIntent = $business->paymentIntents()->findOrFail($paymentIntentId);

        try {
            if ($request->has('payment_method_id')) {
                $paymentIntent = AttachPaymentMethod::withBusinessPaymentIntent($paymentIntent)->data([
                    'payment_method' => $request->input('payment_method_id'),
                ])->process();
            } else {
                    $paymentIntent = Confirm::withBusinessPaymentIntent($paymentIntent)->process();
            }
        } catch (CardException $exception) {
            return Response::json([
                'error' => $exception->getDeclineCode(),
                'error_message' => $exception->getMessage(),
            ], 400);
        }

        return new PaymentIntentResource($paymentIntent);
    }

    /**
     * @param Business $business
     * @param Charge $charge
     * @param ChargeManagerInterface $chargeManager
     */
    public function createCash(Business $business, Charge $charge, ChargeManagerInterface $chargeManager)
    {
        Gate::inspect('operate', [$charge, $business])->authorize();

        try {
            return $chargeManager->createCash($business, $charge);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Business $business
     * @param Charge $charge
     * @param ChargeManagerInterface $chargeManager
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function cancelCharge(Business $business, Charge $charge, ChargeManagerInterface $chargeManager)
    {
        Gate::inspect('operate', [$charge, $business])->authorize();

        try {
            $chargeManager->markAsCanceled($charge);

            return Response::json([
                'success' => true,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param Business $business
     * @param Charge $charge
     * @param ChargeManagerInterface $chargeManager
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateAmountCharge(Business $business, Charge $charge, ChargeManagerInterface $chargeManager)
    {
        Gate::inspect('operate', [$charge, $business])->authorize();

        try {
            $charge->amount = $request->get('amount');
            $charge->update();

            return new ChargeResource($charge);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updatePaymentIntent(Request $request, Business $business, Charge $charge, ChargeManagerInterface $chargeManager)
    {
        try {
            if ($request->has('description')) {
                $chargeManager->updateRemark($charge, $request->input('description'));
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function chargeCompleted (
      Business $business,
      Charge $charge)
    {
      Gate::inspect('operate', [$charge, $business])->authorize();

      return Response::json([
        'completed' => $charge->status === 'succeeded'
      ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
