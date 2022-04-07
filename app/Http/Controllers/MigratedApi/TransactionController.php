<?php

namespace App\Http\Controllers\MigratedApi;

use App\Business;
use App\Business\Charge as ChargeModel;
use App\Enumerations\Business\Channel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\CountryCode;
use App\Exceptions\HitPayLogicException;
use App\Logics\Business\ChargeRepository;
use Carbon\Carbon;
use HitPay\Stripe\Charge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use RuntimeException;

class TransactionController extends Controller
{
    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('currency');
    }

    /**
     * Return a currency list.
     *
     * @param string|null $method
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \ReflectionException
     */
    public function currency(string $method = null)
    {
        if (is_null($method)) {
            $currencies = SupportedCurrencyCode::listConstants();
        } elseif ($method === 'payment_card') {
            $currencies = SupportedCurrencyCode::listConstants();
        } else {
            abort(404);
        }

        foreach ($currencies as $key => $value) {
            // Wrong statement.
            $minimumAmount = 100;

            if (SupportedCurrencyCode::isNormal($value)) {
                $minimumAmount = $minimumAmount / 100;
            }

            $currencies[$key] = [
                'code' => strtoupper($value),
                'name' => Lang::get('misc.currency.'.$value),
                'is_zero_decimal' => SupportedCurrencyCode::isZeroDecimal($value),
                'minimum_amount' => $minimumAmount,
            ];
        }

        $currencies = Collection::make($currencies);
        $currencies->sortBy('name');

        return Response::json($currencies);
    }

    /**
     * Show transaction list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function index(Request $request)
    {
        $business = $this->getBusiness($request);

        $charges = $business->charges();

        $status = $request->get('status');

        if ($status === 'completed') {
            $charges = $charges->where('status', ChargeStatus::SUCCEEDED);
        } elseif ($status === 'cancelled') {
            $charges = $charges->where('status', ChargeStatus::VOID);
        } elseif ($status === 'refunded') {
            $charges = $charges->where('status', ChargeStatus::REFUNDED);
        } else {
            $charges = $charges->whereIn('status', [
                ChargeStatus::SUCCEEDED,
                ChargeStatus::REFUNDED,
                ChargeStatus::VOID,
            ]);
        }

        $charges = $charges->orderByDesc('created_at')->paginate();

        return $this->getTransactionListObject($charges, $business);
    }

    /**
     * Get today collection.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function getTodayCollection(Request $request)
    {
        $business = $this->getBusiness($request);

        $today = Carbon::today();

        $charge = $business->charges()->where('status', ChargeStatus::SUCCEEDED)->orderByDesc('closed_at')->first();

        if ($charge instanceof ChargeModel) {
            $charge = $this->generateTransactionObject($charge, $business);
        }

        $collection = $business->charges()->selectRaw('currency, sum(amount) as sum')
            ->where('status', ChargeStatus::SUCCEEDED)
            ->whereDate('closed_at', $today)
            ->groupBy('currency')
            ->pluck('sum', 'currency', 'business_id')
            ->toArray();

        foreach ($collection as $code => $amount) {
            $data[] = [
                'currency_code' => strtoupper($code),
                'name' => Lang::get('misc.currency.'.$code),
                'amount' => number_format(getReadableAmountByCurrency($code, $amount), 2),
            ];
        }

        return Response::json([
            'date' => Date::now()->toDateString(),
            'collection' => $data ?? [],
            'last_transaction' => $charge,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     */
    public function show(Request $request, string $id)
    {
        $business = $this->getBusiness($request);

        $charge = $business->charges()->whereIn('status', [
            ChargeStatus::SUCCEEDED,
            ChargeStatus::REFUNDED,
            ChargeStatus::VOID,
        ])->findOrFail($id);

        return $this->generateTransactionObject($charge, $business);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $chargeId
     * @param string $method
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function showRepeat(Request $request, string $chargeId, string $method = null)
    {
        $business = $this->getBusiness($request);

        $charge = $business->charges()->findOrFail($chargeId);

        $keepTrying = 0;

        do {
            if ($charge->status === ChargeStatus::SUCCEEDED
                || $charge->status === ChargeStatus::FAILED
                || $charge->status === ChargeStatus::CANCELED) {
                return $this->generateTransactionObject($charge, $business);
            }

            usleep(500000);

            $charge = $charge->refresh();

            $keepTrying++;
        } while ($keepTrying < 60);

        throw new RuntimeException;
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function log(Request $request)
    {
        $business = $this->getBusiness($request);

        $methods = ChargeRepository::$hitPayMethods;

        if ($business->country !== CountryCode::SINGAPORE) {
            $methods = array_flip($methods);
            $methods = Arr::except($methods, 'paynow');
            $methods = array_flip($methods);
        }

        $request->merge([
            'currency_code' => strtolower($request->get('currency_code')),
        ]);

        $data = $this->validate($request, [
            'method' => [
                'required',
                Rule::in($methods),
            ],
            'currency_code' => [
                'required',
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

        $charge->channel = Channel::POINT_OF_SALE;
        $charge->payment_provider = 'hitpay';
        $charge->payment_provider_charge_method = $data['method'];
        $charge->status = ChargeStatus::SUCCEEDED;
        $charge->closed_at = $freshTimestamp;
        $charge->currency = $data['currency_code'];
        $charge->remark = $data['remark'] ?? null;
        $charge->amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });
        $charge->home_currency = $charge->currency;
        $charge->home_currency_amount = $charge->amount;

        /**
         * @var \App\Business\Charge $charge
         */
        $charge = DB::transaction(function () use ($business, $charge) {
            return $business->charges()->save($charge);
        });

        $charge = $charge->refresh();

        return $this->generateTransactionObject($charge, $business);
    }

    /**
     * Send receipt to an email.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     */
    public function send(Request $request, string $id)
    {
        $business = $this->getBusiness($request);

        $charge = $business->charges()->where('status', ChargeStatus::SUCCEEDED)->find($id);

        if (!$charge) {
            $charge = $business->charges()->where('status', ChargeStatus::SUCCEEDED)
                ->where('business_target_type', 'business_order')
                ->where('business_target_id', $id)
                ->firstOrFail();
        }

        $data = $this->validate($request, [
            'email' => 'required|email',
        ]);

        ChargeRepository::sendReceipt($charge, $data['email']);

        return Response::json([
            'message' => 'The receipt has been sent to the email address.',
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function refund(Request $request, string $id)
    {
        $business = $this->getBusiness($request);

        Gate::inspect('operate', $business)->authorize();

        $charge = $business->charges()->where('status', ChargeStatus::SUCCEEDED)->findOrFail($id);

        $charge = ChargeRepository::refund($charge);

        return Response::json($this->generateTransactionObject($charge, $business));
    }

    protected function generateTransactionObject(ChargeModel $charge, Business $business)
    {
        $data['id'] = $charge->getKey();
        $data['platform'] = $charge->payment_provider === 'stripe_sg' ? 'stripe' : $charge->payment_provider;
        $data['method'] = $charge->payment_provider_charge_method === 'card'
            ? 'payment_card'
            : $charge->payment_provider_charge_method;

        if ($charge->payment_provider_transfer_type === 'destination'
            || $charge->payment_provider_transfer_type === 'direct') {
            $data['charge_type'] = $charge->payment_provider_transfer_type.'_charge';
        } else {
            $data['charge_type'] = $charge->payment_provider_transfer_type;
        }

        $data['currency_code'] = strtoupper($charge->currency);
        $data['amount'] = getReadableAmountByCurrency($charge->currency, $charge->amount);

        if ($charge->status === 'succeeded') {
            $data['status'] = 'completed';
        } elseif ($charge->status === 'void') {
            $data['status'] = 'cancelled';
        } else {
            $data['status'] = $charge->status;
        }

        $data['remark'] = $charge->remark;
        $data['amount_text'] = getFormattedAmount($charge->currency, $charge->amount, false);

        if ($charge->payment_provider_charge_method === 'card') {
            $card = $charge->data['payment_method_details']['card'] ??
                $charge->data['source']['card'] ?? $charge->data['source'];
            $data['extra_data'] = [
                'type' => $charge->payment_provider_charge_method,
                'card' => [
                    'country' => $card['country'] ?? 'unknown',
                    'brand' => isset($card['brand']) ? strtolower($card['brand']) : 'unknown',
                    'last4' => $card['last4'] ?? '****',
                    'funding' => isset($card['funding']) ? strtolower($card['funding']) : 'unknown',
                    'expiry_date' => ($card['exp_month'] ?? 'MM').'-'.($card['exp_year'] ?? 'YYYY'),
                    'address_zip' => $card['address_zip'] ?? 'unknown',
                    'address_zip_check' => isset($card['address_zip_check'])
                        ? $card['address_zip_check'] === 'pass' : 'unknown',
                    'cvc_check' => isset($card['cvc_check']) ? $card['cvc_check'] === 'pass' : 'unknown',
                ],
            ];
        }

        $data['platform_fee'] = $charge->home_currency
            ? getReadableAmountByCurrency($charge->home_currency, $charge->getTotalFee())
            : null;
        $data['completed_at'] = $charge->closed_at ? $charge->closed_at->getTimestamp() : null;
        $data['completed_at_string'] = $charge->closed_at ? $charge->closed_at->toDateTimeString() : null;

        if ($charge->status === 'refunded' || $charge->status === 'void') {
            $data['refunded_at'] = $charge->closed_at->getTimestamp();
            $data['refunded_at_string'] = $charge->closed_at->toDateTimeString();
        } else {
            $data['refunded_at'] = null;
            $data['refunded_at_string'] = null;
        }

        return $data;
    }

    private function getTransactionListObject(LengthAwarePaginator $paginator, Business $business)
    {
        $currentPage = $paginator->currentPage();

        $pagination = [];
        $pagination['self'] = $paginator->url($currentPage);
        $pagination['first'] = $paginator->url(1);

        if ($currentPage > 1) {
            $pagination['prev'] = $paginator->url($currentPage - 1);
        }

        $lastPage = $paginator->lastPage();

        if ($currentPage < $lastPage) {
            $pagination['next'] = $paginator->url($currentPage + 1);
        }

        $pagination['last'] = $paginator->url($lastPage);

        $data['meta'] = [
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $currentPage,
                'total_pages' => $lastPage,
                'links' => $pagination,
            ],
        ];

        $data['data'] = [];

        foreach ($paginator->items() as $item) {
            $data['data'][] = $this->generateTransactionObject($item, $business);
        }

        return $data;
    }
}
