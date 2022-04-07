<?php

namespace App\Http\Controllers\Api\Business;

use App\Business\PaymentRequest;
use App\Business;
use App\Enumerations\Business\PluginProvider;
use App\Http\Resources\Business\PaymentRequestResource;
use App\Http\Requests\PaymentRequestRequest;
use App\Http\Controllers\Controller;
use App\Manager\PaymentRequestManagerInterface;
use App\Manager\BusinessManagerInterface;
use App\Manager\ApiKeyManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response as HttResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentRequestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/payment-requests",
     *     tags={"PaymentRequests"},
     *     summary="Get list of payment requests",
     *     description="Returns list of payment requests",
     *     @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/PaymentRequestResource")
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *     ),
     *     @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *     )
     * )
     */
    public function index()
    {
        $user       = Auth::user();
        $business   = $user->businessesOwned()->first();

        return PaymentRequestResource::collection($business->paymentRequests()->limit(10)->get());
    }

    /**
     * @OA\Post(
     *      path="/payment-requests",
     *      tags={"PaymentRequests"},
     *      summary="Store new payment request",
     *      description="Returns payment request data",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/PaymentRequestRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/PaymentRequestResource")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function store(
        PaymentRequestRequest $request,
        PaymentRequestManagerInterface $paymentRequestManager,
        BusinessManagerInterface $businessManager
    ) {
        $user           = Auth::user();

        if ($request->headers->has('X-BUSINESS-API-KEY')) {
            $business = $user->businessesOwned()->first();
        }
        else {
            if (!$user->businesses->contains($request->business_id))
                    App::abort(403, 'User doesnt have access to the business');

                $business = Business::find($request->business_id);
            }

        $apiKey         = $business->apiKeys()->first();
        $businessApiKey = $apiKey->api_key;

        if ($request->headers->has('X-PLATFORM-KEY')) {
            $platform = Business::wherePlatformKey($request->header('X-PLATFORM-KEY'))->first();

            if ($platform) {
                if ($platform->getKey() !== $business->getKey() && $platform->platform_enabled) {
                    $platformPaymentMethods = $businessManager->getByBusinessAvailablePaymentMethods($platform, $request->get('currency'));
                } else {
                    unset($platform);
                }
            } else {
                App::abort(403, 'The provider platform key is invalid.');
            }
        }

        $provider = PluginProvider::getProviderByChanel($request->get('channel'));

        if ($paymentMethods = $businessManager->getBusinessProviderPaymentMethods($business, $provider, $request->get('currency'))) {
            $paymentMethods = array_flip($paymentMethods);
        } else {
            $paymentMethods = $businessManager->getByBusinessAvailablePaymentMethods($business, $request->get('currency'));
        }

        if (isset($platformPaymentMethods)) {
          $paymentMethods = array_intersect($paymentMethods, $platformPaymentMethods);
        }

        $paymentRequest = $paymentRequestManager->create(
            $request->all(),
            $businessApiKey,
            array_keys($paymentMethods),
            $platform ?? null
        );

        return (new PaymentRequestResource($paymentRequest))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
        ;
    }

    /**
     * @OA\Get(
     *      path="/payment-requests/{payment_request_id}",
     *      tags={"PaymentRequests"},
     *      summary="Get payment request information",
     *      description="Returns payment request data",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="Payment Request id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string",
     *              format="uuid"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/PaymentRequestResource")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function show(PaymentRequest $paymentRequest)
    {
        Gate::inspect('show', $paymentRequest)->authorize();

        return new PaymentRequestResource($paymentRequest);
    }

    /**
     * @OA\Put(
     *      path="/payment-requests/{payment_request_id}",
     *      tags={"PaymentRequests"},
     *      summary="Update existing payment request",
     *      description="Returns updated payment request data",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="Payment Request id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string",
     *              format="uuid"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/PaymentRequestRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/PaymentRequestResource")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function update(PaymentRequestRequest $request, PaymentRequest $paymentRequest, PaymentRequestManagerInterface $paymentRequestManager)
    {
        Gate::inspect('update', $paymentRequest)->authorize();

        $paymentRequest = $paymentRequestManager->update($paymentRequest, $request->all());

        return (new PaymentRequestResource($paymentRequest))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED)
        ;
    }

    /**
     * @OA\Delete(
     *      path="/payment-requests/{payment_request_id}",
     *      tags={"PaymentRequests"},
     *      summary="Delete existing payment request",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="Payment Request id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string",
     *              format="uuid"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function destroy(PaymentRequest $paymentRequest, PaymentRequestManagerInterface $paymentRequestManager)
    {
        Gate::inspect('destroy', $paymentRequest)->authorize();

        $paymentRequestManager->delete($paymentRequest);

        return HttResponse::json(['success' => true]);
    }
}
