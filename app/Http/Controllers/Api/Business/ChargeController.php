<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Business\Charge as ChargeModel;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\Charge;
use App\Jobs\SendExportedCharges;
use App\Logics\Business\ChargeRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Cache;

class ChargeController extends Controller
{
    /**
     * Relationships can be loaded.
     *
     * @var array
     */
    public static $relationships = [
        'target',
    ];

    /**
     * ChargeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paginator = ChargeRepository::getList($request, $business);

        return Charge::collection($paginator);
    }

    /**
     * Log a charge via HitPay.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Http\Resources\Business\Charge
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function store(Request $request, BusinessModel $business)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($business->country === CountryCode::SINGAPORE) {
            $methods = ChargeRepository::$hitPayMethods;
        } else {
            $methods = Arr::except(ChargeRepository::$hitPayMethods, 'paynow');
        }

        $data = $this->validate($request, [
            'customer_id' => [
                'nullable',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'method' => [
                'required',
                'string',
                Rule::in(array_merge($methods, [
                    'charge_link',
                ])),
            ],
            'currency' => [
                'required',
                'string',
                Rule::in(SupportedCurrencyCode::listConstants()),
            ],
            'amount' => [
                'required',
                'decimal:0,2',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $charge = new ChargeModel;

        $freshTimestamp = $charge->freshTimestamp();

        $charge->currency = $data['currency'];
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        if ($data['method'] === 'charge_link') {
            $charge->channel = Channel::LINK_SENT;
            $charge->status = ChargeStatus::REQUIRES_CUSTOMER_ACTION;
            $charge->expires_at = $freshTimestamp->addMonth();
        } else {
            $charge->channel = Channel::POINT_OF_SALE;
            $charge->payment_provider = 'hitpay';
            $charge->payment_provider_charge_method = $data['method'];
            $charge->home_currency = $charge->currency;
            $charge->home_currency_amount = $charge->amount;
            $charge->status = ChargeStatus::SUCCEEDED;
            $charge->closed_at = $freshTimestamp;
        }

        if (!empty($data['customer_id'])) {
            $charge->setCustomer($business->customers()->findOrFail($data['customer_id']), true);
        }

        $charge = DB::transaction(function () use ($business, $charge) {
            return $business->charges()->save($charge);
        });

        $charge = $charge->refresh();

        return new Charge($charge);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @return \App\Http\Resources\Business\Charge
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BusinessModel $business, ChargeModel $charge)
    {
        Gate::inspect('view', $business)->authorize();

        $charge->load(static::$relationships);

        return new Charge($charge);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @return \App\Http\Resources\Business\Charge
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, BusinessModel $business, ChargeModel $charge)
    {
        Gate::inspect('operate', $business)->authorize();

        $charge = ChargeRepository::update($request, $charge);

        $charge->load(static::$relationships);

        return new Charge($charge);
    }

    /**
     * Refund or void an existing transaction.
     *
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @return \App\Http\Resources\Business\Charge
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(BusinessModel $business, ChargeModel $charge)
    {
        Gate::inspect('operate', $business)->authorize();

        $charge = ChargeRepository::refund($charge);

        $charge->load(static::$relationships);

        return new Charge($charge);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function send(Request $request, BusinessModel $business, ChargeModel $charge)
    {
        Gate::inspect('operate', $business)->authorize();

        if (!in_array($charge->status, [
            ChargeStatus::REQUIRES_CUSTOMER_ACTION,
            ChargeStatus::SUCCEEDED,
            ChargeStatus::REFUNDED,
            ChargeStatus::VOID,
        ])) {
            App::abort(403, 'You can\'t send link with status "'.$charge->status.'".');
        }

        if ($charge->customer_email) {
            $nullable = 'nullable';
        }

        $data = $this->validate($request, [
            'customer_id' => [
                $nullable ?? 'required_without:email',
                Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
            ],
            'email' => [
                $nullable ?? 'required_without:customer_id',
                'email',
            ],
        ]);

        ChargeRepository::sendReceipt($charge, $data['email'], $sendWithoutSetting = true);

        return Response::json([
            'success' => true,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function export(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'starts_at' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ends_at' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $startsAt = Date::parse($data['starts_at']);
        $endsAt = Date::parse($data['ends_at']);

        SendExportedCharges::dispatch($business, [
            'from_date' => $startsAt->startOfDay(),
            'to_date' => $endsAt->endOfDay(),
        ]);

        return Response::json([
            'success' => true,
        ]);
    }

    /**
     * Helper to validate request for new charge.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param string|null $paymentProviderName
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param array $currencies
     *
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     */
    protected function validateForNewCharge(
        Request $request, BusinessModel $business, string $paymentProviderName = null, array $rules = [],
        array $messages = [], array $customAttributes = [], array $currencies = []
    ) {
        switch ($business->payment_provider) {

            case PaymentProvider::STRIPE_MALAYSIA:
            case PaymentProvider::STRIPE_SINGAPORE:
                if (empty($currencies)) {
                    $currencies = [
                        $business->currency,
                    ];
                }

                break;

            default:
                App::abort(403, 'Invalid payment provider. Please contact us.');
        }

        $tryDetectCurrency = $request->get('currency');

        if ($tryDetectCurrency) {
            if (in_array($tryDetectCurrency, SupportedCurrencyCode::zeroDecimal())) {
                $tryValidateAmountRule = 'int';
            } elseif (in_array($tryDetectCurrency, SupportedCurrencyCode::normal())) {
                $tryValidateAmountRule = 'decimal:0,2';
            }
        }

        $data = $this->validate($request, $rules + [
                'customer_id' => [
                    'nullable',
                    Rule::exists('business_customers', 'id')->where('business_id', $business->getKey()),
                ],
                'currency' => [
                    'required',
                    'string',
                    Rule::in($currencies),
                ],
                'amount' => [
                    'required',
                    $tryValidateAmountRule ?? 'numeric',
                ],
                'remark' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ], $messages, $customAttributes);

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', $paymentProviderName ?? $business->payment_provider)
            ->first();

        if (!$paymentProvider) {
            App::abort(403, 'You have to setup Stripe account before you can continue.');
        }

        return [
            $data,
            $paymentProvider,
        ];
    }

    /**
     * Daily sales report
     *
     * @param Request $request
     * @param BusinessModel $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getDailyReport(Request $request, BusinessModel $business)
    {
        $days_num = 7;

        $cache_key = "business_{$business->id}_sales_daily_report_{$days_num}";

        if (Cache::has($cache_key)) {
            $data = Cache::get($cache_key);
        } else {
            // create empty values
            $data = [];

            $end = new \DateTimeImmutable();
            $start = $end->modify("-{$days_num} days");
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end);
            foreach ($period as $date) {
                /* @var \DateTime $date */
                $day = $date->format('Y-m-d');

                $data[$day] = [
                    'date' => $day,
                    'sum' => number_format(getReadableAmountByCurrency($business->currency, 0), 2)
                ];
            }

            // get real values from the DB
            $results = $business
                ->setConnection('mysql_read')
                ->charges()
                ->selectRaw('DATE_FORMAT(closed_at, \'%Y-%m-%d\') as date, IFNULL(SUM(home_currency_amount), 0) as sum')
                ->where('status', ChargeStatus::SUCCEEDED)
                ->whereDate('closed_at', '>=', Carbon::today()->subDays($days_num)->setHour(0)->setMinute(0))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->pluck('sum', 'date')
                ->toArray();

            // merge with empty data
            foreach ($results as $date => $sum) {
                $data[$date]['sum'] = number_format(getReadableAmountByCurrency($business->currency, $sum), 2);
            }

            $expires_at = Carbon::now()->addHours(1);

            Cache::put($cache_key, $data, $expires_at);
        }

        return Response::json([
            'data' => array_values($data),
        ]);
    }
}
