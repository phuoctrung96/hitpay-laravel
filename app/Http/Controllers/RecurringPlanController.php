<?php

namespace App\Http\Controllers;

use App\Business;
use App\Business\RecurringBilling;
use App\Enumerations\Business\RecurringPlanStatus;
use App\Enumerations\PaymentProvider;
use App\Logics\Business\ChargeRepository;
use App\Manager\BusinessManagerInterface;
use App\Notifications\NotifySubscriptionCardUpdated;
use HitPay\Stripe\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Stripe\Customer as StripeCustomer;
use Stripe\Exception\CardException;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Throwable;

class RecurringPlanController extends Controller
{
    /**
     * @param Business $business
     * @param RecurringBilling $recurringPlan
     * @param BusinessManagerInterface $businessManager
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function show(
        Business $business,
        RecurringBilling $recurringPlan,
        BusinessManagerInterface $businessManager
    )
    {
        if (!$recurringPlan->isCompleted()) {
            switch (true) {
                case $business->paymentProviders()->where('payment_provider', $business->payment_provider)
                        ->count() <= 0:
                case !$recurringPlan->isValid():
                    App::abort(404);
            }
        }

        $recurringPlan->price = $recurringPlan->getPrice();
        $recurringPlan->load('customer');

        $stripePublishableKey = $businessManager->getStripePublishableKey($business);

        return Response::view('recurring-plan', [
            'business' => $business,
            'business_logo' => $business->logo ? $business->logo->getUrl() : asset('hitpay/logo-000036.png'),
            'recurring_plan' => $recurringPlan,
            'stripePublishableKey' => $stripePublishableKey
        ]);
    }

    public function getSetupIntent(Business $business, RecurringBilling $recurringPlan)
    {
        Customer::new('stripe_sg');

        $customer = $this->getStripeCustomer($business, $recurringPlan);

        $recurringPlan->payment_provider_customer_id = $customer->id;
        $recurringPlan->save();

        $intent = SetupIntent::create([ 'customer' => $customer->id ]);

        return Response::json([ 'client_secret' => $intent->client_secret ]);
    }

    public function update(Request $request, Business $business, RecurringBilling $recurringPlan)
    {
        switch (true) {
            case $recurringPlan->payment_provider === PaymentProvider::DBS_SINGAPORE:
                return Response::json([
                    'message' => '"'.$recurringPlan->name.'" can\'t be updated because it uses auto direct debit.',
                ], 400);
            case $business->paymentProviders()->where('payment_provider', $business->payment_provider)->count() <= 0:
            case $recurringPlan->isCompleted():
                return Response::json([
                    'message' => '"'.$recurringPlan->name.'" is completed.',
                ], 422);
            case !$recurringPlan->isValid():
                return Response::json([
                    'message' => '"'.$recurringPlan->name.'" is invalid anymore. Please contact '.$business->getName()
                        .' for further information.',
                ], 404);
        }

        $data = $this->validate($request, [
            'payment_method_id' => [
                'required',
                'string',
            ],
        ]);

        Customer::new('stripe_sg');

        $paymentMethod = PaymentMethod::retrieve($data['payment_method_id']);

        $customer = $this->getStripeCustomer($business, $recurringPlan);

        $recurringPlan->payment_provider_customer_id = $customer->id;

        try {
            $paymentMethod->attach([
                'customer' => $customer->id,
            ]);
        } catch (CardException $exception) {
            return Response::json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $recurringPlan->payment_provider = $business->payment_provider;
        $recurringPlan->payment_provider_payment_method_id = $paymentMethod->id;

        $data = $recurringPlan->data;

        $data['stripe'] = [
            'customer' => $customer->toArray(),
            'payment_method' => $paymentMethod->toArray(),
        ];

        $recurringPlan->data = $data;

        $oldStatus = $recurringPlan->status;

        $recurringPlan->status = RecurringPlanStatus::ACTIVE;
        $recurringPlan->save();

        if ($oldStatus === RecurringPlanStatus::SCHEDULED) {
            try {
                $charge = $recurringPlan->charge();
            } catch (Throwable $exception) {
                $recurringPlan->payment_provider_payment_method_id = null;

                $data = $recurringPlan->data;

                unset($data['stripe']['payment_method']);

                $recurringPlan->data = $data;
                $recurringPlan->status = $oldStatus;
                $recurringPlan->save();

                if ($exception instanceof CardException) {
                    return Response::json([
                        'message' => $exception->getMessage(),
                    ], 422);
                }

                throw $exception;
            }

            try {
                ChargeRepository::sendReceipt($charge, $charge->customer_email);
            } catch (Throwable $exception) {
                Log::critical('Send receipt failed for recurring payment, ID: '.$recurringPlan->getKey());
            }
        } elseif ($oldStatus === RecurringPlanStatus::ACTIVE) {
            $recurringPlan->business->notify(new NotifySubscriptionCardUpdated($recurringPlan));
        }

        $redirect_url = $recurringPlan->redirect_url ? $recurringPlan->redirect_url.'/'.'?type=recurring'.'&reference='.$recurringPlan->getKey().'&status='.$recurringPlan->status : null;

        return Response::json([
            'redirect_url' => $redirect_url
        ]);
    }

    private function getStripeCustomer(Business $business, Business\RecurringBilling $recurringPlan)
    {
        if ($recurringPlan->payment_provider_customer_id) {
            return StripeCustomer::retrieve($recurringPlan->payment_provider_customer_id);
        }

        return StripeCustomer::create([
            'description' => 'Recurring Plan for '.$business->getName(),
            'email' => $recurringPlan->customer_email,
            'metadata' => [
                'business_id' => $recurringPlan->business_id,
                'recurring_plan_id' => $recurringPlan->id,
            ],
            'name' => $recurringPlan->customer_name,
        ]);
    }
}
