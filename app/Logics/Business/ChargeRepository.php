<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Charge;
use App\Business\Charge as ChargeModel;
use App\Business\ChargeReceiptRecipient;
use App\Business\Order as OrderModel;
use App\Business\Refund as RefundModel;
use App\Business\Transfer as TransferModel;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\Event;
use App\Exceptions\HitPayLogicException;
use App\Logics\ConfigurationRepository;
use App\Notifications\NotifyOrderVoided;
use App\Notifications\NotifyOrderConfirmation;
use Exception;
use HitPay\Stripe\Charge as StripeCharge;
use HitPay\Stripe\Core;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Stripe\Exception\InvalidRequestException;
use Stripe\Refund as StripeRefund;
use Stripe\Transfer;

class ChargeRepository
{
    public static $hitPayMethods = [
        'cash',
        'cheque',
        'bank_transfer',
        'paynow',
    ];

    public static function getList(Request $request, Business $business)
    {
        $paginator = $business->setConnection('mysql_read')->charges()->with('target');

        $paginator->with([
            'walletTransactions' => function (MorphMany $query) {
                $query->where('event', \App\Enumerations\Business\Wallet\Event::RECEIVED_FROM_CHARGE)->with([
                    'walletTransactions' => function (MorphMany $query) {
                        $query->where('event', \App\Enumerations\Business\Wallet\Event::CONFIRMED_CHARGE);
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

        $per_page = null;
        if ($currentBusinessUser->isCashier()) {
            $per_page = 10;
        } else {
            if ($request->has('per_page')) {
                $per_page = (int)$request->per_page;
            }
        }

        $paginator = $paginator->paginate($per_page);

        $paginator->transform(function (Charge $charge) {
            if ($charge->walletTransactions->count()) {
                $received = $charge->walletTransactions->where('event', \App\Enumerations\Business\Wallet\Event::RECEIVED_FROM_CHARGE)->first();

                if ($received) {
                    $confirmed = $received->walletTransactions->where('event', \App\Enumerations\Business\Wallet\Event::CONFIRMED_CHARGE)->first();

                    $charge->is_confirmed = $confirmed instanceof Business\Wallet\Transaction;
                }
            }

            return $charge;
        });

        $paginator->appends('status', $status);
        $paginator->appends('order_related_only', $orderRelatedOnly);

        return $paginator;
    }

    // todo move create here

    /**
     * Update an existing charge.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\Charge $chargeModel
     *
     * @return \App\Business\Charge
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, ChargeModel $chargeModel): ChargeModel
    {
        $data = Validator::validate($request->all(), [
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $chargeData['remark'] = $data['remark'] ?? null;

        $chargeModel = DB::transaction(function () use ($chargeModel, $chargeData) : ChargeModel {
            $chargeModel->update($chargeData);

            return $chargeModel;
        }, 3);

        return $chargeModel;
    }

    /**
     * @param \App\Business\Charge $chargeModel
     *
     * @return \App\Business\Charge
     * @throws \Throwable
     */
    public static function refund(ChargeModel $chargeModel, int $amount = null): ?ChargeModel
    {
        if ($chargeModel->status === ChargeStatus::REFUNDED || $chargeModel->status === ChargeStatus::VOID) {
            return $chargeModel;
        } elseif ($chargeModel->status !== ChargeStatus::SUCCEEDED) {
            App::abort(403, 'You can only refund a charge which is succeeded.');
        } elseif (in_array($chargeModel->payment_provider,
            collect(Core::$countries)->pluck('payment_provider')->toArray())) {
            if ($chargeModel->payment_provider_transfer_type === 'destination') {
                StripeCharge::new($chargeModel->payment_provider);

                $transfers = Business\Transfer::query()
                    ->where('business_id', $chargeModel->business_id)
                    ->where('business_charge_id', $chargeModel->id)
                    ->get();

                $transfersCount = $transfers->where('payment_provider_transfer_type', 'transfer')->count();

                if ($transfersCount === 0) {
                    Log::info('No transfer detected for Charge ID: ' . $chargeModel->getKey());

                    return null;
                } elseif ($transfersCount > 1) {
                    Log::info('More than 1 transfer detected for Charge ID: ' . $chargeModel->getKey());

                    return null;
                }

                $transfer = $transfers->where('payment_provider_transfer_type', 'transfer')->first();

                /**
                 * @var \App\Business\Transfer $transfer
                 */
                if ($transfer->payment_provider_transfer_type === 'transfer') {
                    $balance = $chargeModel->balance ?? $chargeModel->amount;

                    try {
                        if (is_null($amount) || $balance - $amount === 0) {
                            $transferReversal = Transfer::createReversal($transfer->payment_provider_transfer_id);
                        } else {
                            $amountHasBeenTransferred = bcsub($chargeModel->amount, $chargeModel->getTotalFee());
                            $percentageOfRequest = bcdiv($amount, $chargeModel->amount, 8);
                            $amountToBeReversed = bcmul($amountHasBeenTransferred, $percentageOfRequest);

                            $transferReversal = Transfer::createReversal($transfer->payment_provider_transfer_id, [
                                'amount' => $amountToBeReversed,
                            ]);
                        }
                    } catch (InvalidRequestException $exception) {
                        // No specific error code returned by Stripe, just get the `invalid_request_error` code, we
                        // will try to search in transfer and see if a reversed is done.
                        //
                        // TODO - one more possibility is that business account has insufficient balance.
                        if ($exception->getError()->type === 'invalid_request_error' && is_null($exception->getError()->decline_code)) {
                            throw new HitPayLogicException($exception->getError()->message);
                        }

                        throw $exception;
                    }

                    $transferModel = new TransferModel;
                    $transferModel->business_id = $transfer->business_id;
                    $transferModel->payment_provider = $transfer->payment_provider;
                    $transferModel->payment_provider_account_id = $transfer->payment_provider_account_id;
                    $transferModel->payment_provider_transfer_type = $transferReversal->object;
                    $transferModel->payment_provider_transfer_id = $transferReversal->id;
                    $transferModel->payment_provider_transfer_method = 'unknown';
                    $transferModel->currency = $transferReversal->currency;
                    $transferModel->amount = $transferReversal->amount;
                    $transferModel->status = 'succeeded';
                    $transferModel->data = $transferReversal->toArray();

                    DB::transaction(function () use ($transferModel) {
                        $transferModel->save();
                    }, 3);

                    try {
                        if (is_null($amount) || $balance - $amount === 0) {
                            $refund = StripeRefund::create([
                                'charge' => $chargeModel->payment_provider_charge_id,
                                'metadata' => [
                                    'platform' => Config::get('app.name'),
                                    'version' => ConfigurationRepository::get('platform_version'),
                                    'environment' => Config::get('app.env'),
                                    'business_id' => $chargeModel->business_id,
                                    'business_charge_id' => $chargeModel->getKey(),
                                ],
                            ]);
                        } else {
                            $refund = StripeRefund::create([
                                'charge' => $chargeModel->payment_provider_charge_id,
                                'amount' => $amount,
                                'metadata' => [
                                    'platform' => Config::get('app.name'),
                                    'version' => ConfigurationRepository::get('platform_version'),
                                    'environment' => Config::get('app.env'),
                                    'business_id' => $chargeModel->business_id,
                                    'business_charge_id' => $chargeModel->getKey(),
                                ],
                            ]);
                        }
                    } catch (InvalidRequestException $exception) {
                        throw $exception;
                    }

                    return static::createRefund($chargeModel, $refund, $amount);
                }

                throw new HitPayLogicException('Unknown transfer type detected for transfer.');
            } elseif ($chargeModel->payment_provider_transfer_type === 'direct') {
                StripeCharge::new($chargeModel->payment_provider, $chargeModel->payment_provider_account_id);

                try {
                    $balance = $chargeModel->balance ?? $chargeModel->amount;

                    if (is_null($amount) || $balance - $amount === 0) {
                        $refund = StripeRefund::create([
                            'charge' => $chargeModel->payment_provider_charge_id,
                            'metadata' => [
                                'platform' => Config::get('app.name'),
                                'version' => ConfigurationRepository::get('platform_version'),
                                'environment' => Config::get('app.env'),
                                'business_id' => $chargeModel->business_id,
                                'business_charge_id' => $chargeModel->getKey(),
                            ],
                        ]);
                    } else {
                        $refund = StripeRefund::create([
                            'charge' => $chargeModel->payment_provider_charge_id,
                            'amount' => $amount,
                            'metadata' => [
                                'platform' => Config::get('app.name'),
                                'version' => ConfigurationRepository::get('platform_version'),
                                'environment' => Config::get('app.env'),
                                'business_id' => $chargeModel->business_id,
                                'business_charge_id' => $chargeModel->getKey(),
                            ],
                        ]);
                    }
                } catch (InvalidRequestException $exception) {
                    throw $exception;
                }

                return static::createRefund($chargeModel, $refund, $amount);
            } elseif ($chargeModel->payment_provider_transfer_type === 'application_fee') {
                StripeCharge::new($chargeModel->payment_provider);

                try {
                    $balance = $chargeModel->balance ?? $chargeModel->amount;

                    if (is_null($amount) || $balance - $amount === 0) {
                        $refund = StripeRefund::create([
                            'charge' => $chargeModel->payment_provider_charge_id,
                            'reverse_transfer' => true,
                            'refund_application_fee' => false,
                            'metadata' => [
                                'platform' => Config::get('app.name'),
                                'version' => ConfigurationRepository::get('platform_version'),
                                'environment' => Config::get('app.env'),
                                'business_id' => $chargeModel->business_id,
                                'business_charge_id' => $chargeModel->getKey(),
                            ],
                        ]);
                    } else {
                        $refund = StripeRefund::create([
                            'charge' => $chargeModel->payment_provider_charge_id,
                            'amount' => $amount,
                            'reverse_transfer' => true,
                            'refund_application_fee' => false,
                            'metadata' => [
                                'platform' => Config::get('app.name'),
                                'version' => ConfigurationRepository::get('platform_version'),
                                'environment' => Config::get('app.env'),
                                'business_id' => $chargeModel->business_id,
                                'business_charge_id' => $chargeModel->getKey(),
                            ],
                        ]);
                    }
                } catch (InvalidRequestException $exception) {
                    throw $exception;
                }

                return static::createRefund($chargeModel, $refund, $amount);
            }

            throw new HitPayLogicException('Unknown charge type detected for Charge ID: ' . $chargeModel->getKey());
        } elseif ($chargeModel->payment_provider === 'hitpay'
            && in_array($chargeModel->payment_provider_charge_method, static::$hitPayMethods)) {
            $balance = $chargeModel->balance ?? $chargeModel->amount;

            if (is_null($amount) || $balance - $amount === 0) {
                $chargeModel->status = ChargeStatus::VOID;
                $chargeModel->balance = null;
                $chargeModel->refunded_at = $chargeModel->freshTimestamp();

                if ($chargeModel->target instanceof OrderModel) {
                    $chargeModel->target->notify(new NotifyOrderVoided($chargeModel->target));
                }
            } elseif ($balance - $amount < 0) {
                throw new HitPayLogicException('The amount must be lesser than '
                    . getFormattedAmount($chargeModel->currency, $chargeModel->amount) . '.');
            } else {
                $chargeModel->balance = $balance - $amount;
            }

            return DB::transaction(function () use ($chargeModel) {
                $chargeModel->save();

                return $chargeModel;
            });
        }

        throw new HitPayLogicException('Unexpected scenario. Not HitPay or Stripe payment provider detected.');
    }

    /**
     * Create refund. (Log refund)
     *
     * @param \App\Business\Charge $chargeModel
     * @param \Stripe\Refund $stripeRefund
     * @param int|null $amount
     *
     * @return mixed
     * @throws \Throwable
     */
    private static function createRefund(ChargeModel $chargeModel, StripeRefund $stripeRefund, int $amount = null)
    {
        $refundModel = new RefundModel;
        $refundModel->business_charge_id = $chargeModel->getKey();
        $refundModel->payment_provider = $chargeModel->payment_provider;
        $refundModel->payment_provider_account_id = $chargeModel->payment_provider_account_id;
        $refundModel->payment_provider_refund_type = $stripeRefund->object;
        $refundModel->payment_provider_refund_id = $stripeRefund->id;
        $refundModel->payment_provider_refund_method = $chargeModel->payment_provider_charge_method;
        $refundModel->amount = $stripeRefund->amount;
        $refundModel->data = $stripeRefund->toArray();

        $balance = $chargeModel->balance ?? $chargeModel->amount;

        if (is_null($amount) || $balance - $amount === 0) {
            $chargeModel->status = ChargeStatus::REFUNDED;
            $chargeModel->balance = null;
            $chargeModel->refunded_at = $chargeModel->freshTimestamp();

            if ($balance - $amount < 0) {
                Log::critical('Refund amount more than original? Check charge ID: ' . $chargeModel->getKey());
            }
        } else {
            $chargeModel->balance = $balance - $amount;
        }

        return DB::transaction(function () use ($chargeModel, $refundModel) {
            $chargeModel->save();
            $refundModel->save();

            return $chargeModel;
        }, 3);
    }

    /**
     * @param \App\Business\Charge $chargeModel
     * @param string $email
     *
     * @throws \ReflectionException
     * @throws \Exception
     */
    /**
     * @param \App\Business\Charge $chargeModel
     * @param string $email
     *
     * @throws \ReflectionException
     * @throws \Exception
     */
    public static function sendReceipt(ChargeModel $chargeModel, string $email, $sendWithoutSetting = false)
    {
        if ($chargeModel->status !== ChargeStatus::SUCCEEDED) {
            App::abort(403, 'You can only send a succeeded charge.');
        }

        $orderModel = $chargeModel->target;
        $business = $chargeModel->business;

        if ($orderModel instanceof OrderModel) {
            if ($orderModel->customer_email === null) {
                $orderModel->customer_email = $email;
                $orderModel->save();
            }

            $orderModel->notify(new NotifyOrderConfirmation($orderModel));
        }
        if ($chargeModel->payment_provider_charge_method === 'card_present') {
            $stripeChargeData = ($chargeModel->data['stripe']['charge'] ?? $chargeModel->data);

            $application = [
                'application' => [
                    'identifier' => $stripeChargeData['payment_method_details']['card_present']['receipt']['dedicated_file_name'],
                    'name' => $stripeChargeData['payment_method_details']['card_present']['receipt']['application_preferred_name'],
                ],
            ];
        }
        if ($sendWithoutSetting || $business->subscribedEvents()->where('event', Event::CUSTOMER_RECEIPT)->first() != null) {
            Mail::send('hitpay-email.receipt', [
                    'charge_id' => $chargeModel->id,
                    'business_logo' => $business->logo ? $business->logo->getUrl() : asset('hitpay/logo-000036.png'),
                    'business_name' => $business->name,
                    'business_email' => $business->email,
                    'charge_date' => $chargeModel->closed_at->toDateTimeString(),
                    'charge_remark' => $chargeModel->remark,
                    'charge_method' => $chargeModel->getChargeDetails(),
                    'charged_amount' => getFormattedAmount($chargeModel->currency, $chargeModel->amount),
                    'payment_provider_id' => $chargeModel->payment_provider_charge_id,
                    'target_name' => $orderModel->name ?? null,
                    'recurring_plan_dbs_dda_reference' => isset($orderModel->dbs_dda_reference) ? 'Bill Reference:' . $orderModel->dbs_dda_reference : null,
                    'tax_setting' => $chargeModel->invoice->tax_setting ?? null,
                ] + ($application ?? []), function (Message $message) use ($business, $email) {
                $message->to($email)->subject('Your Receipt from ' . $business->name . ' via ' . Config::get('app.name'));
            });
        }

        if ($business->customers()->where('email', $email)->first() === null) {
            try {
                $business->customers()->create([
                    'email' => $email,
                ]);

                if ($chargeModel->customer_email === null) {
                    $chargeModel->customer_email = $email;
                    $chargeModel->save();
                }

                ChargeReceiptRecipient::create([
                    'business_charge_id' => $chargeModel->getKey(),
                    'email' => $email,
                    'sent_at' => Date::now(),
                ]);
            } catch (Exception $exception) {
                //
            }
        }
    }
}
