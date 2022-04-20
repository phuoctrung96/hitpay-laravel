<?php

namespace App\Jobs;

use App\Business;
use App\Business\Order;
use App\Business\SubscriptionPlan;
use App\Enumerations\Business\ChargeStatus;
use App\Notifications\SendFile;
use App\Role;
use App\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use League\Csv\Writer;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class SendExportedCharges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    public $data;

    public $user;

    public $fieldsToBeIgnored = [
        'Channel',
        'Plugin',
        'Plugin Reference',
        'Method',
        'Additional Reference',
        'Order ID',
        'Customer Name',
        'Receipt Recipient',
        'Remark',
        'Product(s)',
        'Payment Details',
        'Store URL',
        'Terminal ID',
    ];

    /**
     * Create a new job instance.
     *
     * @param \App\Business $business
     * @param array $data
     * @param \App\User|null $user
     * @param array|null $fields
     */
    public function __construct(Business $business, array $data, User $user = null, array $fields = null)
    {
        $this->business = $business;
        $this->data = $data;
        $this->user = $user;

        if (is_null($fields)) {
            $this->fieldsToBeIgnored = [];
        } else {
            $this->fieldsToBeIgnored = collect($this->fieldsToBeIgnored)->filter(function ($field) use ($fields) {
                return !in_array($field, $fields);
            })->toArray();
        }
    }

    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');

        $filters = [];

        $charges = $this->business->charges()->with('receiptRecipients', 'target')->whereIn('status', [
            ChargeStatus::SUCCEEDED,
            ChargeStatus::REFUNDED,
            ChargeStatus::VOID,
        ])->whereNotNull('closed_at');

        if (key_exists('payment_method', $this->data) && !empty($this->data['payment_method'])) {
            if ($this->data['payment_method'] === 'paynow_online') {
                $charges->where('payment_provider', 'dbs_sg')
                    ->where('payment_provider_charge_method', $this->data['payment_method']);
            } elseif ($this->data['payment_method'] === 'cash' || $this->data['payment_method'] === 'paynow') {
                $charges->where('payment_provider', 'hitpay')
                    ->where('payment_provider_charge_method', $this->data['payment_method']);
            } else {
                $charges->where('payment_provider', $this->business->payment_provider)
                    ->where('payment_provider_charge_method', $this->data['payment_method']);
            }

            $filters[] = $this->data['payment_method'];
        }

        if (key_exists('channel', $this->data) && !empty($this->data['channel'])) {
            $charges->where('channel', $this->data['channel']);

            $filters[] = $this->data['channel'];

            if (key_exists('plugin_provider', $this->data) && !empty($this->data['plugin_provider'])) {
                $charges->where('plugin_provider', $this->data['plugin_provider']);

                $filters[] = $this->data['plugin_provider'];
            }
        }

        if (key_exists('from_date', $this->data) && key_exists('to_date', $this->data)) {
            $fromDate = Date::parse($this->data['from_date']);
            $toDate = Date::parse($this->data['to_date']);
        } else {
            $today = Date::now();
            $fromDate = $today->startOfMonth();
            $toDate = $today->endOfMonth();
        }

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $charges->whereDate('closed_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $charges->whereDate('closed_at', '<=', $toDate->endOfDay()->toDateTimeString());

        $charges = $charges->orderBy('closed_at')->get();

        $csv = Writer::createFromString('');

        $csv->insertOne(collect([
            '#',
            'ID',
            'Channel',
            'Plugin',
            'Plugin Reference',
            'Method',
            'Status',
            'Additional Reference',
            'Order ID',
            'Customer Name',
            'Receipt Recipient',
            'Remark',
            'Product(s)',
            'Currency',
            'Amount',
            'Refunded Amount',
            'Cashback',
            'Cashback Admin Fee',
            'Converted Amount in SGD',
            'HitPay Platform Fee Amount in SGD (Exclusive of Stripe Fee)',
            'All Inclusive Fee Amount in SGD',
            'Net Amount in SGD',
            'Payment Details',
            'Completed Date',
            'Store URL',
            'Terminal ID',
        ])->filter(function ($field) {
            return !in_array($field, $this->fieldsToBeIgnored);
        })->toArray());

        $i = 1;

        $data = [];

        /** @var \App\Business\Charge $charge */
        foreach ($charges as $charge) {
            $emails = $charge->receiptRecipients->pluck('email');

            $emails->push($charge->customer_email);

            $target = $charge->target;
            $remark = $charge->remark;
            $orderId = null;
            $orderedProducts = null;

            if ($target instanceof Order) {
                $orderId = $target->getKey();
                $orderedProducts = Collection::make();

                foreach ($target->products as $product) {
                    $name = $product->name;

                    if ($product->description) {
                        $name .= ' ('.$product->description.')';
                    }

                    $orderedProducts->add($name);
                }

                $orderedProducts = $orderedProducts->implode(' / ');

                $emails->push($target->customer_email);
            } elseif ($target instanceof SubscriptionPlan) {
                $remark = $target->name.' '.$remark;
            }

            if ($charge->balance !== null) {
                $refundedAmount = $charge->amount - $charge->balance;
            } else {
                // When customer performs a full refund balance will be null
                $refundedAmount = $charge->status === ChargeStatus::REFUNDED ? $charge->amount : 0;
            }

            if ($charge->refunds->where('is_cashback',1)->count()){
                $status = 'succeeded_with_cashback';
            }
            elseif ($charge->status === 'succeeded'
                && $charge->amount - ($charge->balance ?? 0)
                !== $charge->amount){
                $status = 'partially_refunded';
            }
            else{
                $status = $charge->status;
            }

            $cashback_amount = $charge->refunds->where('is_cashback',1)->first()->amount ?? 0;

            $singleData = collect([
                '#' => $i++,
                'ID' => $charge->getKey(),
                'Channel' => $charge->channel,
                'Plugin' => $charge->plugin_provider,
                'Plugin Reference' => $charge->plugin_provider_reference,
                'Method' => $charge->payment_provider_charge_method,
                'Status' => $status,
                'Additional Reference' => $charge->payment_provider_charge_id,
                'Order ID' => $orderId ??
                    $charge->plugin_provider_order_id ?? $charge->plugin_provider_reference ?? null,
                'Customer Name' => $charge->customer_name,
                'Receipt Recipient' => $emails->unique()->implode(' / '),
                'Remark' => $remark,
                'Product(s)' => $orderedProducts ?? null,
                'Currency' => strtoupper($charge->currency),
                'Amount' => getReadableAmountByCurrency($charge->currency, $charge->amount),
                'Refunded Amount' => getReadableAmountByCurrency($charge->currency, $refundedAmount),
                'Cashback' => getReadableAmountByCurrency($charge->currency, $cashback_amount),
                'Cashback Admin Fee' => $cashback_amount ? '0.00' : 0,
                'Converted Amount in SGD' => $charge->currency !== 'sgd' && $charge->exchange_rate
                    ? getReadableAmountByCurrency($charge->home_currency, $charge->home_currency_amount)
                    : null,
                'HitPay Platform Fee Amount (Exclusive of Stripe Fee)' => $charge->payment_provider_transfer_type
                === 'direct'
                && $charge->home_currency
                    ? getReadableAmountByCurrency($charge->home_currency, $charge->getTotalFee()) : '',
                'All Inclusive Fee' => ($charge->payment_provider_transfer_type === 'destination'
                    || $charge->payment_provider_transfer_type === 'manual'
                    || $charge->payment_provider_transfer_type === 'wallet'
                    || $charge->payment_provider_transfer_type === 'application_fee')
                && $charge->home_currency
                    ? getReadableAmountByCurrency($charge->home_currency, $charge->getTotalFee()) : '',
                'Net Amount' => $charge->home_currency ? getReadableAmountByCurrency($charge->home_currency,
                    bcsub($charge->home_currency_amount, $charge->getTotalFee())) : '',
                'Payment Details' => $charge->getChargeDetails(),
                'Completed Date' => $charge->closed_at->toDateTimeString(),
                'Store URL' => $charge->getStoreURL(),
                'Terminal ID' => $charge->data['hitpay']['terminal']['serial_number'] ?? null,
            ])->filter(function ($field, $index) {
                return !in_array($index, $this->fieldsToBeIgnored);
            })->toArray();

            $data[] = $singleData;
        }

        $csv->insertAll($data);

        $fromDate = $fromDate->toDateString();
        $toDate = $toDate->toDateString();

        if (count($filters)) {
            $filters = ' ('.implode(', ', $filters).')';
        } else {
            $filters = '';
        }

        if ($this->user instanceof User) {
            $this->user->notify(new SendFile($this->business->getName().' - Exported Transactions', [
                'Please find attached the exported transactions'.$filters.' from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        } else {
            $this->business->notify(new SendFile('Your Exported Transactions', [
                'Please find attached your exported transactions'.$filters.' from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        }

        /** @var Business\BusinessUser $businessAdmins */
        $businessAdmins = $this->business->businessUsers()
            ->with('user')
            ->where('role_id', Role::admin()->id)
            ->get();

        foreach ($businessAdmins as $businessAdmin) {
            $businessAdmin->user->notify(new SendFile($this->business->getName().' - Exported Transactions', [
                'Please find attached the exported transactions'.$filters.' from '.$fromDate.' to '.$toDate,
            ], ($fromDate.' - '.$toDate), $csv->getContent()));
        }
    }
}
