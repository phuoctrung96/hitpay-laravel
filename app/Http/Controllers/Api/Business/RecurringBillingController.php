<?php

namespace App\Http\Controllers\Api\Business;

use App\Actions\Business\RecurringPlan\CanCreateRecurringPlan;
use App\Business;
use App\Business\RecurringBilling;
use App\Business\SubscriptionPlan;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\RecurringBillingEvent;
use App\Enumerations\Business\RecurringCycle;
use App\Enumerations\Business\RecurringPlanStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Exceptions\CollectionFailedException;
use App\Http\Requests\RecurringBillingChargeRequest;
use App\Http\Resources\Business\RecurringBilling as RecurringBillingResource;
use App\Http\Requests\RecurringBillingRequest;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessRecurringPaymentCallback;
use App\Notifications\NotifySubscriptionRenewalFailure;
use App\Notifications\SendSubscriptionCanceledEmail;
use App\Notifications\SendSubscriptionUpdateCardLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\Exception\CardException;
use Throwable;

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

        if (!CanCreateRecurringPlan::withBusiness($business)->process()) {
            App::abort(403, "Transaction Failed. Please complete card payments setup under Settings > Payment Methods in your hitpay dashboard.");
        }

        $customer = $business->customers()->where('email',$data['customer_email'])->first();

        if (!$customer){
            if (!$request->customer_name)
                App::abort(403, 'The customer name field is required');

            $customer = $business->customers()->create(['name' => $request->customer_name, 'email' => $request->customer_email]);
        }

        if (isset($data['plan_id']))
            $subscriptionPlan = $business->subscriptionPlans()->find($data['plan_id']);

        $recurringPlan = new RecurringBilling;

        $startsAt = Date::createFromFormat('Y-m-d', $data['start_date']);

        if (!isset($data['send_email']) || empty($data['send_email'])) {
            $data['send_email'] = false;
        } else {
            $data['send_email'] = true;
        }

        if (!isset($data['save_card']) || empty($data['save_card'])) {
            $data['save_card'] = false;
        } else {
            $data['save_card'] = true;
        }

        $recurringPlan->dbs_dda_reference = strtoupper('RP'.Str::random(9));
        $recurringPlan->business_recurring_plans_id = isset($subscriptionPlan) ? $subscriptionPlan->id :  null;
        $recurringPlan->name = isset($subscriptionPlan) ? $subscriptionPlan->name :  '';
        $recurringPlan->description = isset($subscriptionPlan) ? $subscriptionPlan->description : null;
        $recurringPlan->reference = $data['reference'] ?? null;
        $recurringPlan->currency = strtolower($request->currency ? $data['currency'] : $business->currency);
        $recurringPlan->price = $request->amount ? getRealAmountForCurrency($recurringPlan->currency,  $data['amount']) : $subscriptionPlan->price;
        $recurringPlan->cycle = isset($subscriptionPlan) ? $subscriptionPlan->cycle : RecurringCycle::SAVE_CARD;
        $recurringPlan->status = RecurringPlanStatus::SCHEDULED;
        $recurringPlan->expires_at = $startsAt->endOfDay();
        $recurringPlan->redirect_url = $data['redirect_url'] ?? null;
        $recurringPlan->send_email = $data['send_email'];
        $recurringPlan->save_card = $data['save_card'];
        $recurringPlan->webhook = $data['webhook'] ?? null;
        $recurringPlan->payment_methods = $data['payment_methods'] ?? [PaymentMethodType::CARD];

        if (isset($data['times_to_be_charge'])) {
            $recurringPlan->times_to_be_charged = $data['times_to_be_charge'];
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

    /**
     * @OA\Get(
     *      path="charge/recurring-billing/{recurring_billing}",
     *      tags={"RecurringBilling"},
     *      summary="Charge recurring billing",
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
    public function charge(RecurringBillingChargeRequest $request, RecurringBilling $recurringBilling)
    {
        try {
            $charge = $recurringBilling->charge(1, $request->amount, $request->currency);
            ProcessRecurringPaymentCallback::dispatch($recurringBilling, RecurringBillingEvent::CHARGE_SUCCESS, 'succeeded',  $charge);

            return [
                'payment_id' => $charge->getKey(),
                'recurring_billing_id' => $recurringBilling->getkey(),
                'amount' => getReadableAmountByCurrency($charge->currency, $charge->amount),
                'currency' => $charge->currency,
                'status' => $charge->status,
            ];
        } catch (CollectionFailedException $exception) {
            $recurringBilling->failed_reason = $exception->getDeclineCode();
            $recurringBilling->save();

            $error = 'Failed: Recurring Plan via Collection, ID:'.$recurringBilling->id.' => ' .$exception->getMessage();

            Log::channel('failed-collection')->critical("Failed: Recurring Plan via Collection\n"
                .'Business ID: '.$recurringBilling->business_id."\n"
                .'Business Name: '.$recurringBilling->business->name."\n"
                .'Subscribed Recurring Plan ID: '.$recurringBilling->id."\n"
                .'Decline Code: '.$exception->getDeclineCode()."\n"
                .'Error Message: '.$exception->getMessage());

            ProcessRecurringPaymentCallback::dispatch($recurringBilling, RecurringBillingEvent::RECURRENT_BILLING_STATUS, 'failed');
            $recurringBilling->notify(new SendSubscriptionUpdateCardLink);
            $recurringBilling->business->notify(new NotifySubscriptionRenewalFailure($recurringBilling));

            return $error;
        } catch (CardException $exception) {
            $recurringBilling->failed_reason = $exception->getDeclineCode();
            $recurringBilling->save();

            $error = 'Failed: Recurring Plan via Card Payment, ID:'.$recurringBilling->id.' => '
                .$exception->getMessage();

            ProcessRecurringPaymentCallback::dispatch($recurringBilling, RecurringBillingEvent::RECURRENT_BILLING_STATUS, 'failed');
            $recurringBilling->notify(new SendSubscriptionUpdateCardLink);
            $recurringBilling->business->notify(new NotifySubscriptionRenewalFailure($recurringBilling));

            return $error;
        } catch (Throwable $exception) {
            ProcessRecurringPaymentCallback::dispatch($recurringBilling, RecurringBillingEvent::RECURRENT_BILLING_STATUS, 'failed');
            $error = 'Error when charging recurring plan, ID:'.$recurringBilling->id.' => '.get_class($exception)
                .' '.$exception->getMessage();

            return $error;
        }
    }
}
