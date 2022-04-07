<?php

namespace App\Http\Controllers\Api\Business;

use App\Business;
use App\Business\RecurringBilling;
use App\Business\SubscriptionPlan;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\RecurringPlanStatus;
use App\Http\Resources\Business\RecurringBilling as RecurringBillingResource;
use App\Http\Requests\RecurringBillingRequest;
use App\Http\Controllers\Controller;
use App\Notifications\SendSubscriptionCanceledEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class RecurringBillingController extends Controller
{

    /**
     * @OA\Post(
     *      path="/recurring-billing",
     *      tags={"RecurringBilling"},
     *      summary="Store new recurring billing",
     *      description="Store new recurring billing",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/RecurringBillingRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful opera tion",
     *          @OA\JsonContent(ref="#/components/schemas/RecurringBillingResource")
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
    public function store(RecurringBillingRequest $request)
    {
        $user = Auth::user();

        $business = $user->businessesOwned()->first();
        $data = $request->all();

        $customer = $business->customers()->where('email',$data['customer_email'])->first();

        if (!$customer){
            if (!$request->customer_name)
                App::abort(403, 'The customer name field is required');

            $customer = $business->customers()->create(['name' => $request->customer_name, 'email' => $request->customer_email]);
        }

        $subscriptionPlan = $business->subscriptionPlans()->find($data['plan_id']);

        $recurringPlan = new RecurringBilling;

        $startsAt = Date::createFromFormat('Y-m-d', $data['start_date']);

        if (!isset($data['send_email']) || empty($data['send_email'])) {
            $data['send_email'] = false;
        } else {
            $data['send_email'] = true;
        }

        $recurringPlan->dbs_dda_reference = strtoupper('RP'.Str::random(9));
        $recurringPlan->business_recurring_plans_id = $data['plan_id'];
        $recurringPlan->name = $subscriptionPlan->name;
        $recurringPlan->description = $subscriptionPlan->description;
        $recurringPlan->currency = strtolower($request->currency ? $data['currency'] : $subscriptionPlan->currency);
        $recurringPlan->price = $request->amount ? getRealAmountForCurrency($recurringPlan->currency,  $data['amount']) : $subscriptionPlan->price;
        $recurringPlan->cycle = $subscriptionPlan->cycle;
        $recurringPlan->status = RecurringPlanStatus::SCHEDULED;
        $recurringPlan->expires_at = $startsAt->endOfDay();
        $recurringPlan->redirect_url = $data['redirect_url'] ?? null;
        $recurringPlan->send_email = $data['send_email'];
        $recurringPlan->payment_methods = $data['payment_methods'] ?? [PaymentMethodType::CARD];

        if (isset($data['times_to_charge'])) {
            $recurringPlan->times_to_be_charged = $data['times_to_charge'];
            $recurringPlan->times_charged = 0;
        }

        $recurringPlan->setCustomer($customer, true);

        $recurringPlan = $business->recurringBillings()->save($recurringPlan);

        return (new RecurringBillingResource($recurringPlan))
            ->response()
            ->setStatusCode(\Illuminate\Http\Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *      path="/recurring-billing/{recurring-billing_id}",
     *      tags={"RecurringBilling"},
     *      summary="Get recurring billing information",
     *      description="Returns recurring billing data",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="Recurring billing id",
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
     *          @OA\JsonContent(ref="#/components/schemas/RecurringBillingResource")
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
    public function show(RecurringBilling $recurringBilling)
    {
        return new RecurringBillingResource($recurringBilling);
    }

    /**
     * @OA\Get(
     *      path="/recurring-billing/{recurring-billing_id}",
     *      tags={"RecurringBilling"},
     *      summary="Cancel recurring billing",
     *      description="Returns recurring billing data",
     *      @OA\Parameter(
     *          name="X-BUSINESS-API-KEY",
     *          in="header",
     *          required=true,
     *          description="Business API Key"
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="Recurring billing id",
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
     *          @OA\JsonContent(ref="#/components/schemas/RecurringBillingResource")
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
    public function destroy(RecurringBilling $recurringBilling)
    {
        $recurringBilling->status = RecurringPlanStatus::CANCELED;
        $recurringBilling->save();

        $recurringBilling->notify(new SendSubscriptionCanceledEmail);

        return new RecurringBillingResource($recurringBilling);
    }
}
