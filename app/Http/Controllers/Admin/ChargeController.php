<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Business\Stripe\Charge\PaymentIntent\Capture;
use App\Business\Charge;
use App\Business\PaymentIntent;
use App\Business\Refund;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PaymentMethodType;
use App\Exceptions\HitPayLogicException;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedChargesToAdmin;
use App\Jobs\SendExportedRefundsToAdmin;
use App\Notifications\NotifyAdminAboutNonIdentifiableChargeSource;
use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ChargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index(Request $request)
    {
        $paginator = Charge::with([
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
        ])->with('target', 'refunds');

        $status = $request->get('status');
        $status = strtolower($status);

        if ($request->has('currency') && $request->has('amount')) {
            $currency = $request->get('currency');
            $amount = $request->get('amount');

            if ($currency && $amount) {
                $amount = getRealAmountForCurrency($currency, $amount);

                $paginator->where('business_charges.currency', $currency)->where('business_charges.amount', $amount);
            }

            if ($status === 'refunded') {
                $paginator->whereIn('business_charges.status', [
                    ChargeStatus::REFUNDED,
                    ChargeStatus::VOID,
                ]);
            } elseif ($status === 'failed') {
                $paginator->whereIn('business_charges.status', [
                    ChargeStatus::FAILED,
                    ChargeStatus::CANCELED,
                ]);
            } else {
                $status = 'succeeded';

                $paginator->where('business_charges.status', ChargeStatus::SUCCEEDED);
            }
        } elseif ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            if ($keyword) {
                if(filter_var($keyword, FILTER_VALIDATE_EMAIL)) {
                    $paginator->select('business_charges.*');
                    $paginator->where('business_charges.customer_email', $keyword);
                } else if ((preg_match('/^' . preg_quote('DICN', '/') . '/', $keyword)) || (preg_match('/^' . preg_quote('ch_', '/') . '/', $keyword))) {
                    $paginator->select('business_charges.*');
                    $paginator->where('business_charges.payment_provider_charge_id', $keyword);
                } else {
                    $paginator->select('business_charges.*');
                    $paginator->where('business_charges.id', $keyword);
                }
            }
        } else {
            if ($status === 'refunded') {
                $paginator->whereIn('business_charges.status', [
                    ChargeStatus::REFUNDED,
                    ChargeStatus::VOID,
                ]);
            } elseif ($status === 'failed') {
                $paginator->whereIn('business_charges.status', [
                    ChargeStatus::FAILED,
                    ChargeStatus::CANCELED,
                ]);
            } else {
                $status = 'succeeded';

                $paginator->where('business_charges.status', ChargeStatus::SUCCEEDED);
            }
        }

        $paginator = $paginator->orderByDesc('business_charges.id')->paginate();

        if (isset($id) && count($paginator->items())) {
            $status = $paginator->items()[0]->status;
        } elseif (!$status) {
            $status = 'succeeded';
        }

        $paginator->appends('status', $status);

        return Response::view('admin.charge-index', compact('paginator', 'status'));
    }

    public function export(Request $request)
    {
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

        SendExportedChargesToAdmin::dispatch($data, $request->user());

        return Response::json([
            'success' => true,
        ]);
    }

    public function show(Request $request, Charge $charge)
    {
        return Response::view('admin.charge-single', compact('charge'));
    }

    public function showUncapturedPage(Request $request)
    {
        if ($request->has('charge_id')) {
            $chargeId = $request->get('charge_id');

            if (is_string($chargeId) && strlen($chargeId) > 0) {
                $result = $this->isUncapturedCharge(Charge::find($chargeId));

                if ($result instanceof Charge) {
                    $charge = $result;
                } elseif (is_string($result)) {
                    $errorMessage = $result;
                }
            }
        }

        return Response::view('admin.charge.uncaptured', [
            'charge' => $charge ?? false,
            'errorMessage' => $errorMessage ?? null,
        ]);
    }

    public function capture(Charge $charge)
    {
        $result = $this->isUncapturedCharge($charge, true);

        $response = Response::redirectToRoute('admin.charge.uncaptured', [ 'charge_id' => $charge->getKey() ]);

        if ($result instanceof PaymentIntent) {
            try {
                Capture::withBusinessPaymentIntent($result)->process();
            } catch (Exception $exception) {
                $exceptionClassName = get_class($exception);

                Log::info(
                    "{$exceptionClassName} - {$exception->getMessage()} ({$exception->getFile()}:{$exception->getLine()})\n{$exception->getTraceAsString()}"
                );

                return $response->with('errorMessage', $exception->getMessage());
            }
        } elseif (is_string($result)) {
            return $response->with('errorMessage', $result);
        }

        return $response->with('successMessage', "The charge (ID : {$charge->getKey()}) has been captured.");
    }

    protected function isUncapturedCharge(?Charge $charge, bool $returnPaymentIntent = false)
    {
        if (!$charge instanceof Charge) {
            return 'Charge not found with the given ID.';
        }

        if ($charge->status !== ChargeStatus::REQUIRES_PAYMENT_METHOD) {
            return "This charge is not in status 'requires_payment_method', but '{$charge->status}'.";
        }

        $paymentIntents = $charge->paymentIntents()->get();

        if ($paymentIntents->where('status', 'succeeded')->count() > 0) {
            return "This charge is already having succeeded payment intents, but the charge status is not succeeded. Please contact developers.";
        }

        $cardPresented = $paymentIntents->where('payment_provider_method', PaymentMethodType::CARD_PRESENT);

        if ($cardPresented->count() === 0) {
            return "No card presented payment intent found for this charge.";
        }

        if ($cardPresented->count() > 1) {
            return "More than 1 presented payment intent found for this charge. Please contact developer.";
        }

        if ($returnPaymentIntent) {
            return $cardPresented->first();
        }

        return $charge;
    }

    public function markAsRefund(Request $request, Charge $charge)
    {
        $balance = $charge->balance ?? $charge->amount;

        $data = $request->validate([
            'amount' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'max:'.getReadableAmountByCurrency($charge->currency, $balance, function (string $currency) {
                    throw new HitPayLogicException(sprintf('The currency [%s] is invalid.', $currency));
                }),
            ],
        ], [
            'amount.decimal' => 'The amount can have maximum of 2 decimals.',
        ]);

        $amount = getRealAmountForCurrency($charge->currency, $data['amount']);

        $refund = new Refund;
        $refund->id = Str::orderedUuid()->toString();
        $refund->business_charge_id = $charge->getKey();
        $refund->payment_provider = 'hitpay';
        $refund->payment_provider_account_id = $charge->business_id;
        $refund->payment_provider_refund_method = 'undefined';
        $refund->amount = $amount;
        $refund->payment_provider_refund_id = $refund->id;
        $refund->payment_provider_refund_type = 'manual';
        $refund->remark = 'succeeded';

        if ($balance - $amount <= 0) {
            $charge->status = ChargeStatus::REFUNDED;
            $charge->balance = null;
            $charge->closed_at = $charge->freshTimestamp();
        } else {
            $charge->balance = $balance - $amount;
        }

        DB::transaction(function () use ($charge, $refund) {
            $charge->save();
            $refund->save();
        });

        if ($charge->status === ChargeStatus::REFUNDED) {
            Session::flash('success_message', 'The charge has been fully refunded.');
        } else {
            Session::flash('success_message', 'The charge has been partially refunded.');
        }

        return Response::redirectToRoute('admin.charge.show', $charge->getKey());
    }

    public function failedRefund(Request $request)
    {
        $paginator = Refund::with([
            'business' => function (Relation $query) {
                $query->withTrashed();
            },
            'charge' => function (Relation $query) {
                $query->with('customer');
            },
        ])->where('status', 'failed');

        if ($chargeId = $request->get('charge_id')) {
            $paginator->where('business_charge_id', $chargeId);
        }

        $paginator = $paginator->orderByDesc('id')->paginate();

        return Response::view('admin.failed-refund-index', compact('paginator'));
    }

    public function exportRefund(Request $request)
    {
        $data = $this->validate($request, [
            'starts_at' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ends_at' => [
                'required',
                'date_format:Y-m-d',
            ],
            'type' => [
                'string',
                'nullable'
            ]
        ]);

        SendExportedRefundsToAdmin::dispatch($data, $request->user());

        return Response::json([
            'success' => true,
        ]);
    }

    public function notifyNonIdentifiableChargeSource(Charge $charge){
        Notification::route('slack', config('services.slack.non_identifiable_charge'))
            ->notify(new NotifyAdminAboutNonIdentifiableChargeSource($charge, auth()->user()));

        Session::flash('success_message', 'Notification was sent.');

        return Response::redirectToRoute('admin.charge.show', $charge->getKey());
    }
}
