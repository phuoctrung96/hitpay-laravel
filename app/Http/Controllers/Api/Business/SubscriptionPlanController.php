<?php

namespace App\Http\Controllers\Api\Business;

use App\Business\SubscriptionPlan;
use App\Http\Resources\Business\SubscriptionPlan as SubscriptionPlanResource;
use App\Http\Requests\SubscriptionPlanRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class SubscriptionPlanController extends Controller
{

    /**
     * @OA\Post(
     *      path="/subscription-plan",
     *      tags={"SubscriptionPlan"},
     *      summary="Store new subscription plan",
     *      description="Store new subscription plan",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/SubscriptionPlanRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful opera tion",
     *          @OA\JsonContent(ref="#/components/schemas/SubscriptionPlanResource")
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
    public function store(SubscriptionPlanRequest $request)
    {
        $user = Auth::user();

        $business = $user->businessesOwned()->first();

        $data = $request->all();
        $data['currency'] = strtolower($data['currency'] ? $data['currency'] : $business->currency);
        $data['price'] = getRealAmountForCurrency($data['currency'], $data['amount']);

        $subscriptionPlan = $business->subscriptionPlans()->create($data);

        return (new SubscriptionPlanResource($subscriptionPlan))
            ->response()
            ->setStatusCode(\Illuminate\Http\Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *      path="/subscription-plan/{subscription_plan_id}",
     *      tags={"SubscriptionPlan"},
     *      summary="Get subscription plan information",
     *      description="Returns subscription plan data",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="Subscription Plan id",
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
     *          @OA\JsonContent(ref="#/components/schemas/SubscriptionPlanResource")
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
    public function show(SubscriptionPlan $subscriptionPlan)
    {
        return new SubscriptionPlanResource($subscriptionPlan);
    }
}
