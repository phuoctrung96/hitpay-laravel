<?php

namespace App\Http\Controllers\Dashboard\Business;

use App;
use App\Actions\Business\RecurringPlan\CanCreateRecurringPlan;
use App\Business;
use App\Business\RecurringBilling;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\RecurringCycle;
use App\Enumerations\Business\RecurringPlanStatus;
use App\Http\Controllers\Controller;
use App\Notifications\SendSubscriptionCanceledEmail;
use App\Notifications\SendSubscriptionLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecurringBillingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $paginator = $business->recurringBillings()->with('customer');

        $status = $request->get('status');
        $status = strtolower($status);

        if (!in_array($status, [
            RecurringPlanStatus::ACTIVE,
            RecurringPlanStatus::CANCELED,
            RecurringPlanStatus::SCHEDULED,
            RecurringPlanStatus::COMPLETED,
        ])) {
            $status = RecurringPlanStatus::ACTIVE;
        }

        if ($status === 'scheduled') {
            $paginator->where('status', $status)->whereDate('expires_at', '>=', Date::now()->startOfDay());
        } elseif ($status === 'canceled') {
            $paginator->where(function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('status', 'scheduled')->whereDate('expires_at', '<', Date::now()->startOfDay());
                })->orWhere('status', 'canceled');
            });
        } else {
            $paginator->where('status', $status);
        }

        $paginator = $paginator->orderByDesc('id')->paginate();

        $paginator->appends('status', $status);

        return Response::view('dashboard.business.recurring-plan.index', compact('business', 'paginator', 'status'));
    }

    public function create(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        if (!CanCreateRecurringPlan::withBusiness($business)->process()) {
            App::abort(403, "Transaction Failed. Please complete card payments setup under Settings > Payment Methods in your hitpay dashboard.");
        }

        if ($templateId = $request->get('template_id')) {
            $template = $business->subscriptionPlans()->find($templateId);
        } else {
            $template = null;
        }

        $data['cycle'] = RecurringCycle::collection()->map(function ($value) {
            $value['name'] = ucfirst($value['value']);

            return $value;
        })->toArray();

        return Response::view('dashboard.business.recurring-plan.form', compact('business', 'data', 'template'));
    }

    public function createWithTemplate(Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $templates = $business->subscriptionPlans()->get();

        return Response::view('dashboard.business.recurring-plan.create-template', compact('business', 'templates'));
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws ValidationException
     */
    public function store(Request $request, Business $business)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'customer_id' => [
                'required',
                Rule::exists('business_customers', 'id')->where('business_id', $business->id),
            ],
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
            'price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:1',
                'max:9999999'
            ],
            'cycle' => [
                'required',
                Rule::in(RecurringCycle::listConstants()),
            ],
            'starts_at' => [
                'required',
                'date_format:d/m/Y',
            ],
            'times_to_be_charged' => [
                'nullable',
                'int',
                'min:1',
            ],
            'send_email' => 'boolean'
        ]);

        // TODO - We will need check on the payment providers before allowing business to create recurring plan.

        $customer = $business->customers()->find($data['customer_id']);

        $recurringPlan = new RecurringBilling;

        $startsAt = Date::createFromFormat('d/m/Y', $data['starts_at']);

        if ($startsAt < now()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Starts date must more or same ' . now()->format('d/m/Y'),
            ]);
        }

        // The format for DBS DDA Reference is  'RP0000AAAAA'

        $paymentMethods = [ PaymentMethodType::CARD ];

        if ($business->country === 'sg') {
            $paymentMethods[] = PaymentMethodType::GIRO;
        }

        $recurringPlan->dbs_dda_reference = strtoupper('RP'.Str::random(9));
        $recurringPlan->name = $data['name'];
        $recurringPlan->description = $data['description'] ?? null;
        $recurringPlan->currency = $business->currency;
        $recurringPlan->price = getRealAmountForCurrency($recurringPlan->currency, $data['price']);
        $recurringPlan->cycle = $data['cycle'];
        $recurringPlan->send_email = $data['send_email'];
        $recurringPlan->payment_methods = $paymentMethods;
        $recurringPlan->status = RecurringPlanStatus::SCHEDULED;
        $recurringPlan->expires_at = $startsAt->endOfDay();

        if (isset($data['times_to_be_charged'])) {
            $recurringPlan->times_to_be_charged = $data['times_to_be_charged'];
            $recurringPlan->times_charged = 0;
        }

        $recurringPlan->setCustomer($customer, true);

        $recurringPlan = $business->recurringBillings()->save($recurringPlan);

        if ($recurringPlan->send_email) {
            $recurringPlan->notify(new SendSubscriptionLink);
        }

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.recurring-plan.show', [
                'business_id' => $business->getKey(),
                'b_recurring_billings' => $recurringPlan->getKey(),
            ]),
        ]);
    }

    public function sendLink(Business $business, RecurringBilling $recurringPlan)
    {
        Gate::inspect('operate', $business)->authorize();

        $recurringPlan->notify(new SendSubscriptionLink);

        return Response::json([
            'success' => true,
        ]);
    }

    public function show(Request $request, Business $business, RecurringBilling $recurringPlan)
    {
        Gate::inspect('operate', $business)->authorize();

        $paginator = $recurringPlan->charges()->whereIn('status', [
            ChargeStatus::SUCCEEDED,
        ])->orderByDesc('id')->paginate();

        return Response::view('dashboard.business.recurring-plan.show',
            compact('business', 'recurringPlan', 'paginator'));
    }

    public function edit(Request $request, Business $business, RecurringBilling $recurringPlan)
    {
//        dd($recurringPlan);
        Gate::inspect('operate', $business)->authorize();

        $data['cycle'] = RecurringCycle::collection()->map(function ($value) {
            $value['name'] = ucfirst($value['value']);

            return $value;
        })->toArray();

        return Response::view('dashboard.business.recurring-plan.form', compact('business', 'recurringPlan', 'data'));
    }

    public function update(Request $request, Business $business, RecurringBilling $recurringPlan)
    {
        Gate::inspect('operate', $business)->authorize();

        $data = $this->validate($request, [
            'customer_id' => [
                'required',
                Rule::exists('business_customers', 'id')->where('business_id', $business->id),
            ],
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
            'price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:1',
                'max:9999999'
            ],
            'cycle' => [
                'required',
                Rule::in(RecurringCycle::listConstants()),
            ],
            'starts_at' => [
                'required',
                'date_format:d/m/Y',
                'after_or_equal:'.now()->toDateString(),
            ],
            'times_to_be_charged' => [
                'nullable',
                'int',
                'min:1',
            ],
            'send_email' => 'boolean'
        ]);

        $customer = $business->customers()->find($data['customer_id']);

        $startsAt = Date::createFromFormat('d/m/Y', $data['starts_at']);

        $recurringPlan->name = $data['name'];
        $recurringPlan->description = $data['description'] ?? null;
        $recurringPlan->price = getRealAmountForCurrency($recurringPlan->currency, $data['price']);
        $recurringPlan->cycle = $data['cycle'];
        $recurringPlan->send_email = $data['send_email'];
        $recurringPlan->expires_at = $startsAt->endOfDay();

        if (isset($data['times_to_be_charged'])) {
            $recurringPlan->times_to_be_charged = $data['times_to_be_charged'];
            $recurringPlan->times_charged = 0;
        }

        $recurringPlan->setCustomer($customer, true);

        $recurringPlan->save();

        return Response::json([
            'redirect_url' => URL::route('dashboard.business.recurring-plan.show', [
                'business_id' => $business->getKey(),
                'b_recurring_billings' => $recurringPlan->getKey(),
            ]),
        ]);
    }

    public function cancel(Request $request, Business $business, RecurringBilling $recurringPlan)
    {
        Gate::inspect('operate', $business)->authorize();

        $recurringPlan->status = RecurringPlanStatus::CANCELED;
        $recurringPlan->save();

        $recurringPlan->notify(new SendSubscriptionCanceledEmail);

        $request->session()->flash('success_message', 'The plan has been canceled successfully.');

        return Response::redirectToRoute('dashboard.business.recurring-plan.show', [
            'business_id' => $business->getKey(),
            'b_recurring_billings' => $recurringPlan->getKey(),
        ]);
    }
}
