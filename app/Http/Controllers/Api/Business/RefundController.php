<?php

namespace App\Http\Controllers\Api\Business;

use App\Business\Charge;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\CurrencyCode;
use App\Exceptions\HitPayLogicException;
use App\Exceptions\InsufficientFund;
use App\Http\Requests\RefundRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Refund as RefundResource;
use App\Logics\Business\ChargeRepository;
use App\Services\BusinessUserPermissionsService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class RefundController extends Controller
{

    /**
     * @OA\Post(
     *      path="/refund",
     *      tags={"Refund"},
     *      summary="Store new refund",
     *      description="Returns refund data",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/RefundRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful opera tion",
     *          @OA\JsonContent(ref="#/components/schemas/Refund")
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
    public function store(RefundRequest $request, BusinessUserPermissionsService $businessUserPermissionsService)
    {
        $user = Auth::user();

        $charge = Charge::find($request->payment_id);
        $refundAmount = getRealAmountForCurrency($charge->currency, $request->amount);
        $refund_payment_types = [PaymentMethodType::PAYNOW, PaymentMethodType::CARD];

        if (!$user->businesses->contains($charge->business->id)) {
            App::abort(403, 'User doesnt have access to the business');
        }elseif(!$businessUserPermissionsService->can($user, $charge->business, 'canRefundCharges')){
            App::abort(401, 'User is not authorized to do this action');
        }elseif ($charge->status !== ChargeStatus::SUCCEEDED) {
            App::abort(403, 'You can only refund a charge which is succeeded');
        } elseif ($refundAmount > $charge->amount || $refundAmount + $charge->refunds->sum('amount') > $charge->amount) {
            App::abort(403, 'Total refund amount must be less than payment amount.');
        } elseif (!in_array($charge->payment_provider_charge_method, $refund_payment_types)) {
            App::abort(403, 'Refund is only applicable if the initial charge was made with PayNow or Card');
        }

        if ($charge->payment_provider_charge_method === PaymentMethodType::PAYNOW){
            try {
                $refund = $charge->business->withdrawForRefund($charge, $refundAmount);
            } catch (InsufficientFund $exception) {
                return Response::json([
                    'message' => $exception->getMessage(),
                    'errors' => [
                        'amount' => [$exception->getMessage()]
                    ],
                ], 422);
            } catch (\Exception $exception) {
                return Response::json([
                    'message' => $exception->getMessage(),
                    'errors' => [
                        'amount' => [$exception->getMessage()]
                    ],
                ], 422);
            }
        }
        else{
            try {
                $charge = ChargeRepository::refund($charge, $refundAmount);
                $charge->load('refunds');
                $refund = $charge->refunds->last();
            } catch (HitPayLogicException $exception) {
                return Response::json([
                    'message' => $exception->getMessage(),
                ], 422);
            }
        }

        return (new RefundResource($refund))
            ->response()
            ->setStatusCode(\Illuminate\Http\Response::HTTP_CREATED);
    }
}
