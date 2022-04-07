<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\SubscriptionPlan;
use App\Enumerations\Business\RecurringCycle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $paginator = $business->subscriptionPlans()->orderByDesc('id')->paginate();

        return Response::view('dashboard.business.recurring-plan.template.index', compact('business', 'paginator'));
    }

    public function edit(Business $business, SubscriptionPlan $recurringPlan = null)
    {
        Gate::inspect('operate', $business)->authorize();

        $data['cycle'] = RecurringCycle::collection()->map(function ($value) {
            $value['name'] = ucfirst($value['value']);

            return $value;
        })->toArray();

        return Response::view('dashboard.business.recurring-plan.template.edit', compact('business', 'data', 'recurringPlan'));
    }

    public function update(Request $request, Business $business, SubscriptionPlan $recurringPlan = null)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:16777215',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:1',
            ],
            'cycle' => [
                'required',
                Rule::in(RecurringCycle::listConstants()),
            ],
        ], [
            'price.decimal' => 'The price can have maximum of 2 decimals.',
            'price.min' => 'The price must be at least '.strtoupper($business->currency).'1.',
        ]);

        if (!$recurringPlan) {
            $recurringPlan = new SubscriptionPlan;
        }

        $data['price'] = getRealAmountForCurrency($business->currency, $data['price']);

        $recurringPlan->fill($data);
        $recurringPlan->currency = $business->currency;

        if ($recurringPlan->exists) {
            $recurringPlan->save();
        } else {
            $business->subscriptionPlans()->save($recurringPlan);
        }

        if ($recurringPlan->wasRecentlyCreated) {
            Session::flash('success_message',
                'The recurring plan template \''.$recurringPlan->name.'\' has been created successfully.');
        } else {
            Session::flash('success_message',
                'The recurring plan template \''.$recurringPlan->name.'\' has been updated successfully.');
        }

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.recurring-plan.template.edit', [
                $business->getKey(),
                $recurringPlan->getKey(),
            ]),
        ]);
    }

    public function destroy(Business $business, SubscriptionPlan $recurringPlan)
    {
        Gate::inspect('operate', $business)->authorize();

        $recurringPlan->delete();

        Session::flash('success_message',
            'The recurring plan template \''.$recurringPlan->name.'\' has been deleted successfully.');

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.recurring-plan.template.index', [
                $business->getKey(),
            ]),
        ]);
    }
}
