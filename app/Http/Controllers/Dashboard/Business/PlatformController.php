<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Payout\DBS\Retrieve;
use App\Actions\Business\Payout\DBS\RetrieveForPlatform;
use App\Actions\Business\Payout\Stripe\RetrieveCustomConnect;
use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedCommissions;
use App\Jobs\SendExportedPlatformCharges;
use App\Services\BusinessUserPermissionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    /**
     * ChargeController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paginator = $business->platformCharges()->with('business');

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            if ($keyword) {
                $paginator->where('id', $keyword)
                    ->orWhere('business_target_id', $keyword)
                    ->orWhere('plugin_provider_reference', $keyword)
                    ->orWhere('customer_email', 'like', '%'.$keyword.'%');
            }
        }

        $currentBusinessUser = resolve(BusinessUserPermissionsService::class)->getBusinessUser(Auth::user(), $business);

        $paginator = $paginator->where('status', ChargeStatus::SUCCEEDED)->orderByDesc('id')->paginate();

        return Response::view('dashboard.business.platform', compact('business', 'currentBusinessUser', 'paginator'));
    }

    public function update(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $data = $this->validate($request, [
            'rate' => [
                'required',
                'numeric',
                'decimal:0,2',
                'max:100',
            ],
        ]);

        $business->commission_rate = bcdiv($data['rate'], 100, 4);
        $business->save();

        $request->session()->flash('success_message', 'Commission rate has been updated.');

        return Response::redirectToRoute('dashboard.business.platform.index', $business->getKey());
    }

    public function exportCharge(Request $request, Business $business)
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

        $charges = $business->platformCharges()->whereIn('status', [
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
            App::abort(422, 'You don\'t any platform charge between these date.');
        }

        if (isset($data['fields'])) {
            foreach ($data['fields'] as $field => $value) {
                if ($value) {
                    $fields[] = $field;
                }
            }
        }

        SendExportedPlatformCharges::dispatch($business, [
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

    public function rekey(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        if ($business->platform_enabled) {
            $business->platform_key = Str::lower(implode('-', [
                Str::orderedUuid()->toString(),
                str_pad(time(), 12, '0', STR_PAD_LEFT),
                Str::random(4),
                Str::random(4),
                Str::random(4),
            ]));
            $business->save();

            $request->session()
                ->flash('success_message', 'Platform key has been regenerated.');
        } else {
            $request->session()->flash('danger_message', $business->getName().' isn\'t enable platform yet.');
        }

        return Response::redirectToRoute('dashboard.business.platform.index', $business->getKey());
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function payout(Business $business): \Illuminate\Http\Response
    {
        Gate::inspect('view', $business)->authorize();

        if (!in_array($business->country, [CountryCode::SINGAPORE, CountryCode::MALAYSIA])) {
            throw new \Exception("country {$business->country} not yet support for payouts");
        }

        if ($business->country == CountryCode::SINGAPORE) {
            $actionData = RetrieveForPlatform::withBusiness($business)->process();
        }

        if ($business->country == CountryCode::MALAYSIA) {
            $actionData = \App\Actions\Business\Payout\Stripe\RetrieveForPlatform::withBusiness($business)->process();
        }

        $commissions = $actionData['commissions'] ?? null;

        $provider = $actionData['provider'] ?? null;

        return Response::view('dashboard.business.platform-payout', [
            'business' => $business,
            'paginator' => $commissions,
            'provider' => $provider,
        ]);
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function payoutShow(Business $business, Business\Commission $commission)
    {
        Gate::inspect('view', $business)->authorize();

        $commission->load('charges');

        return Response::view('dashboard.business.platform-payout-show', [
            'business' => $business,
            'commission' => $commission,
        ]);
    }

    /**
     * Export commission for DBS payouts.
     *
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
        ]);

        $charges = $business->commissions();

        $fromDate = Date::parse($data['starts_at']);
        $toDate = Date::parse($data['ends_at']);

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $charges->whereDate('created_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $charges->whereDate('created_at', '<=', $toDate->endOfDay()->toDateTimeString());

        if ($charges->count() < 1) {
            App::abort(422, 'You don\'t any PayNow payout between these date.');
        }

        SendExportedCommissions::dispatch($business, [
            'from_date' => $data['starts_at'],
            'to_date' => $data['ends_at'],
        ]);

        return Response::json([
            'success' => true,
        ]);
    }
}
