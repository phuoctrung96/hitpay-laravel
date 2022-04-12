<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\Order;
use App\Business\Charge;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\Wallet\Event;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use App\Exceptions\InsufficientFund;
use App\Helpers\Pagination;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedCharges;
use App\Logics\Business\ChargeRepository;
use App\Notifications\NotifyOrderRefunded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChargeController extends Controller
{
    /**
     * ChargeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paginator = $business->charges()->with('target');

        // $paginator->with('walletTransactions');
        $paginator->with([
            'walletTransactions' => function (MorphMany $query) {
                $query->where('event', Event::RECEIVED_FROM_CHARGE)->with([
                    'walletTransactions' => function (MorphMany $query) {
                        $query->where('event', Event::CONFIRMED_CHARGE);
                    },
                ]);
            },
        ]);

        $keywords = $request->get('keywords');

        if (strlen($keywords) === 0) {
            $status = $request->get('status');
            $status = strtolower($status);

            if ($status === 'refunded') {
                $paginator->whereIn($paginator->qualifyColumn('status'), [
                    ChargeStatus::REFUNDED,
                    ChargeStatus::VOID,
                ]);
            } elseif ($status === 'failed') {
                $paginator->whereIn($paginator->qualifyColumn('status'), [
                    ChargeStatus::FAILED,
                    ChargeStatus::CANCELED,
                ]);
            } else {
                $status = 'succeeded';

                $paginator->where($paginator->qualifyColumn('status'), ChargeStatus::SUCCEEDED);
            }

            $orderRelatedOnly = $request->get('order_related_only', 0);

            if ($orderRelatedOnly) {
                $paginator->where($paginator->qualifyColumn('business_target_type'), 'business_order');
            }

            $paginator->orderBy($paginator->qualifyColumn('business_id'));
            $paginator->orderByDesc($paginator->qualifyColumn('id'));
        } else {
            if (filter_var($keywords, FILTER_VALIDATE_EMAIL)) {
                $paginator->where('business_charges.customer_email', $keywords);
            } elseif(Str::isUuid($keywords)) {
                $paginator->where(function ($paginator) use ($keywords) {
                    $paginator->orWhere('business_charges.plugin_provider_order_id', $keywords);
                    $paginator->orWhere('business_charges.id', $keywords);
                });
            } else {
                $paginator->select($paginator->qualifyColumn('*'));
                $paginator->leftJoin(
                    'business_subscribed_recurring_plans',
                    $paginator->qualifyColumn('business_target_id'),
                    'business_subscribed_recurring_plans.id'
                )->whereIn($paginator->qualifyColumn('status'), [
                    ChargeStatus::SUCCEEDED,
                    ChargeStatus::REFUNDED,
                    ChargeStatus::VOID,
                ])->where(function (Builder $query) use ($keywords) {
                    // TODO - IF our search feature is slow, here's the issue from.

                    $query->orWhere('business_subscribed_recurring_plans.dbs_dda_reference', $keywords);
                    $query->orWhere($query->qualifyColumn('plugin_provider_order_id'), $keywords);
                    $query->orWhere($query->qualifyColumn('plugin_provider_reference'), $keywords);
                    $query->orWhere(function (Builder $query) use ($keywords) {
                        $i = 0;

                        foreach (explode(' ', $keywords) as $keyword) {
                            $query->where($query->qualifyColumn('remark'), 'LIKE', '%' . $keyword . '%');

                            if ($i++ === 2) {
                                break;
                            }
                        }
                    });
                });
            }
            $status = null;
            $orderRelatedOnly = false;
        }

        $currentBusinessUser = resolve(\App\Services\BusinessUserPermissionsService::class)->getBusinessUser(Auth::user(), $business);

        if ($currentBusinessUser->isCashier()) {
            // cashier role only see 10 transaction
            $perPage = 10;
            $paginator = $paginator->paginate($perPage);
        } else {
            $perPage = $request->get('perPage', Pagination::getDefaultPerPage());
            $paginator = $paginator->paginate($perPage);
        }

        $paginator->transform(function (Charge $charge) {
            if ($charge->walletTransactions->count()) {
                $received = $charge->walletTransactions->where('event', Event::RECEIVED_FROM_CHARGE)->first();

                if ($received) {
                    $confirmed = $received->walletTransactions->where('event', Event::CONFIRMED_CHARGE)->first();

                    $charge->is_confirmed = $confirmed instanceof Business\Wallet\Transaction;
                }
            }

            return $charge;
        });

        $paginator->appends('status', $status);
        $paginator->appends('order_related_only', $orderRelatedOnly);

        return Response::view('dashboard.business.charge.index', compact('currentBusinessUser', 'business', 'paginator', 'status', 'perPage') + [
                'order_related_only' => $orderRelatedOnly,
            ]);
    }

    /**
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Business $business, Charge $charge)
    {
        Gate::inspect('view', $business)->authorize();

        $charge->load('target', 'refunds');

        return Response::view('dashboard.business.charge.show', compact('business', 'charge'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function receipt(Request $request, Business $business, Charge $charge)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'email' => [
                'required',
                'email',
                'max:255',
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
    public function export(Request $request, Business $business)
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
            'channel' => [
                'nullable',
                Rule::in([
                    'payment_gateway',
                    'point_of_sale',
                    'recurrent',
                    'store_checkout',
                ]),
            ],
            'plugin_provider' => [
                'nullable',
                Rule::in(PluginProvider::CHANNELS),
            ],
            'payment_method' => [
                'nullable',
                Rule::in([
                    'paynow_online',
                    'cash',
                    'paynow',
                    'alipay',
                    'card',
                    'card_present',
                    'wechat',
                ]),
            ],
            'fields.*' => [
                'bool',
            ],
        ]);

        $charges = $business->setConnection('mysql_read')->charges()->whereIn('status', [
            ChargeStatus::SUCCEEDED,
            ChargeStatus::REFUNDED,
            ChargeStatus::VOID,
        ])->whereNotNull('closed_at');

        if (!empty($data['payment_method'])) {
            if ($data['payment_method'] === 'paynow_online') {
                $charges->where('payment_provider', 'dbs_sg')
                    ->where('payment_provider_charge_method', $data['payment_method']);
            } elseif ($data['payment_method'] === 'cash' || $data['payment_method'] === 'paynow') {
                $charges->where('payment_provider', 'hitpay')
                    ->where('payment_provider_charge_method', $data['payment_method']);
            } else {
                $charges->where('payment_provider', $business->payment_provider)
                    ->where('payment_provider_charge_method', $data['payment_method']);
            }
        }

        if (!empty($data['channel'])) {
            $charges->where('channel', $data['channel']);

            if (!empty($data['plugin_provider'])) {
                $charges->where('plugin_provider', $data['plugin_provider']);
            }
        }

        $fromDate = Date::parse($data['starts_at']);
        $toDate = Date::parse($data['ends_at']);

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $charges->whereDate('closed_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $charges->whereDate('closed_at', '<=', $toDate->endOfDay()->toDateTimeString());

        if ($charges->count() < 1) {
            App::abort(422, 'You don\'t have any charge between these date.');
        }

        if (isset($data['fields'])) {
            foreach ($data['fields'] as $field => $value) {
                if ($value) {
                    $fields[] = $field;
                }
            }
        }

        SendExportedCharges::dispatch($business, [
            'from_date' => $data['starts_at'],
            'to_date' => $data['ends_at'],
            'payment_method' => $data['payment_method'] ?? null,
            'plugin_provider' => $data['plugin_provider'] ?? null,
            'channel' => $data['channel'] ?? null,
        ], null, $fields ?? []);

        return Response::json([
            'success' => true,
        ]);
    }

    /**
     * @param \App\Business\Charge $charge
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function canRefund(Request $request, Business $business, Charge $charge) {
      return response()->json([
        'canRefund' => $charge->canRefund()
      ]);
    }

    /**
     * @param \App\Business $business
     * @param \App\Business\Charge $charge
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function refund(Request $request, Business $business, Charge $charge)
    {
        Gate::inspect('canRefundCharges', $business)->authorize();

        // if ($charge->balance !== null) {
        //     throw ValidationException::withMessages(['amount' => 'You can only refund for 1 time.']);
        // }

        $maximumAmount = is_int($charge->balance) ? $charge->balance : $charge->amount;
        $maximumRefundable = getReadableAmountByCurrency($charge->currency, $maximumAmount, function (
            string $currency
        ) {
            throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
        });

        $data = $this->validate($request, [
            'amount' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'max:' . $maximumRefundable,
            ],
        ]);

        if (array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== '') {
            $amount = getRealAmountForCurrency($charge->currency, $data['amount'], function (string $currency) {
                throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
            });

            try {
                $charge = ChargeRepository::refund($charge, $amount);
            } catch (HitPayLogicException $exception) {
                return Response::json([
                    'message' => $exception->getMessage(),
                ], 422);
            }
        } else {
            try {
                $charge = ChargeRepository::refund($charge);
            } catch (HitPayLogicException $exception) {
                return Response::json([
                    'message' => $exception->getMessage(),
                ], 422);
            }
        }

        return Response::json([
            'redirect_url' => route('dashboard.business.charge.show', [
                $business->getKey(),
                $charge->getKey(),
            ]),
        ]);
    }

    public function payNowRefund(Request $request, Business $business, Charge $charge)
    {
        Gate::inspect('canRefundCharges', $business)->authorize();

        if ($charge->status !== ChargeStatus::SUCCEEDED) {
            throw ValidationException::withMessages([
                'amount' => 'You can only refund a charge which is succeeded.',
            ]);
        } elseif ($charge->currency !== CurrencyCode::SGD) {
            throw ValidationException::withMessages([
                'amount' => 'You can only refund a SGD charge.',
            ]);
        } elseif ($charge->payment_provider !== PaymentProvider::GRABPAY &&
            $charge->payment_provider !== PaymentProvider::SHOPEE_PAY &&
            $charge->payment_provider !== PaymentProvider::ZIP &&
            ($charge->payment_provider !== PaymentProvider::DBS_SINGAPORE
            || $charge->payment_provider_charge_type !== 'inward_credit_notification'
            || !Str::startsWith($charge->payment_provider_charge_id, 'DICN'))) {
            throw ValidationException::withMessages([
                'amount' => 'This is not a valid PayNow charge. If you have any question, please contact us.',
            ]);
        }

        $balanceAvailable = is_int($charge->balance) ? $charge->balance : $charge->amount;

        $data = $this->validate($request, [
            'amount' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'max:' . getReadableAmountByCurrency($charge->currency, $balanceAvailable, function (string $currency) {
                    throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
                }),
            ],
        ]);

        if (array_key_exists('amount', $data) && is_numeric($data['amount'])) {
            $amountToBeRefunded = getRealAmountForCurrency($charge->currency, $data['amount']);
        } else {
            $amountToBeRefunded = $balanceAvailable;
        }

        try {
            $refund = $business->withdrawForRefund($charge, $amountToBeRefunded);
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

        if ($charge->target instanceof Order && $charge->balance === null) {
            $charge->target->notify(new NotifyOrderRefunded($charge->target));
        }

        return Response::json([
            'id' => $refund->id,
            'amount' => getReadableAmountByCurrency($charge->currency, $refund->amount),
            'redirect_url' => route('dashboard.business.charge.show', [
              $business->getKey(),
              $charge->getKey(),
            ]),
        ]);
    }

    public function getRefundStatus(Business $business, Charge $charge, Business\Refund $refund)
    {
        Gate::inspect('operate', $business)->authorize();

        if ($refund->business_charge_id !== $charge->id && $charge->business_id !== $business->id) {
            App::abort(404);
        }

        // return message if failed.

        return Response::json([
            'id' => $refund->id,
            'business_id' => $charge->business_id,
            'business_charge_id' => $refund->business_charge_id,
            'currency' => $charge->currency,
            'amount' => getReadableAmountByCurrency($charge->currency, $refund->amount),
            'status' => is_null($refund->status) ? 'succeeded' : ($refund->status === 'reverted' ? 'failed' : $refund->status),
            'error_message' => $refund->status === 'failed' ? $refund->remark : '',
        ]);
    }
}
