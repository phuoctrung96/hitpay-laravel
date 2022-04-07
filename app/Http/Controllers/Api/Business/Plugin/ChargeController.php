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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Stripe\Exception\CardException;

class ChargeController extends Controller
{
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
          $stripePaymentIntentManager = $factory->create($request->input('method'));

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

          if ($request->has('amount')) {
              $chargeManager->updateAmount($charge, str_replace(',', '', $request->input('amount')));
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
