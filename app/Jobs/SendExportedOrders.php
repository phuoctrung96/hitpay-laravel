<?php

namespace App\Jobs;

use App\Business;
use App\Enumerations\Business\OrderStatus;
use Illuminate\Support\Facades\Log;
use App\Notifications\SendFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use League\Csv\Writer;
use PDF;

class SendExportedOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    public $data;

    public $status;

    /**
     * Create a new job instance.
     *
     * @param \App\Business $business
     * @param array $data
     */
    public function __construct(Business $business, array $data)
    {
        $this->business = $business;
        $this->data = $data;
        $this->status = $data['status'];
    }

    /**
     * Execute the job.
     *
     * @throws \League\Csv\CannotInsertRecord
     * @throws \ReflectionException
     */
    public function handle()
    {
        $orders = $this->business->orders();

        $status = $this->status;

        if ($status === 'requires_business_action') {
            $orders->where('status', OrderStatus::REQUIRES_BUSINESS_ACTION);
        } elseif ($status === 'requires_customer_action') {
            $orders->where('status', OrderStatus::REQUIRES_CUSTOMER_ACTION);
        } elseif ($status === 'requires_payment_method') {
            $orders->where('status', OrderStatus::REQUIRES_PAYMENT_METHOD);
        } elseif ($status === 'completed') {
            $orders->where('status', OrderStatus::COMPLETED);
        } elseif ($status === 'canceled') {
            $orders->where('status', OrderStatus::CANCELED)->orWhere('status', OrderStatus::EXPIRED);
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

        $orders->whereDate('created_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $orders->whereDate('created_at', '<=', $toDate->endOfDay()->toDateTimeString());

        $orders = $orders->orderBy('created_at')->get();

        $data = [];
        $i = 0;

        /** @var \App\Business\Order $order */
        foreach ($orders as $order) {
            $orderedProducts = Collection::make();
            $orderedProductVariant = Collection::make();

            foreach ($order->products as $product) {
                $name = $product->name;

                $name .= ' (Quantity: ' . $product->quantity . ')';
                $orderedProducts->add($name);

                $variant = $product->description;
                $orderedProductVariant->add($variant);
            }

            if ($order->status === 'requires_business_action') {
                $orderStatus = 'Pending';
            } elseif ($order->status === 'requires_customer_action') {
                $orderStatus = 'Waiting for customer';
            } elseif ($order->status === 'requires_payment_method') {
                $orderStatus = 'Payment in progress';
            } elseif ($order->status === 'completed') {
                $orderStatus = 'Completed';
            } elseif ($order->status === 'canceled') {
                $orderStatus = 'Canceled';
            } elseif ($order->status === 'draft') {
                $orderStatus = 'Draft';
            } elseif ($order->status === 'expired') {
                $orderStatus = 'Expired';
            } elseif ($order->status === 'requires_point_of_sales_action') {
                $orderStatus = 'Requires point of sale action';
            }
            $charge = $order->charges->last();

            $singleData = [
                $i++,
                $order->id,
                $charge ? $charge->id : '',
                $order->reference,
                $order->customer_name,
                $order->customer_email,
                $order->customer_phone_number,
                $order->display('customer_address'),
                $order->currency,
                getFormattedAmount($order->currency, $order->amount, false),
                $order->automatic_discount_reason,
                getFormattedAmount($order->currency, $order->additional_discount_amount),
                $orderStatus,
                $orderedProducts->implode(', '),
                $orderedProductVariant->implode(', '),
                $order->remark ?? "",
                $order->created_at->toDateTimeString(),
                $order->closed_at ? $order->closed_at->toDateTimeString() : null,
            ];

            $data[] = $singleData;
        }

        if ($this->data['docType'] == 'csv') {
            $csv = Writer::createFromString('');

            $csv->insertOne([
                '#',
                'ID',
                'Charge ID',
                'Reference',
                'Customer Name',
                'Customer Email',
                'Customer Phone Number',
                'Customer Address',
                'Currency',
                'Amount',
                'Discount Name',
                'Discount Amount',
                'Status',
                'Products',
                'Variant',
                'Buyer remarks',
                'Ordered Date',
                'Completed Date',
            ]);

            $i = 1;

            $csv->insertAll($data);

            $this->business->notify(new SendFile('Your Exported Orders', [
                'Please find attached your exported orders from ' . $fromDate . ' to ' . $toDate,
            ], ($fromDate . ' - ' . $toDate), $csv->getContent(), $this->data['docType']));
        } elseif ($this->data['docType'] == 'pdf') {
            $vars['data'] = $data;
            $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('hitpay-email.pdf.export-order', $vars);
            $pdf->setPaper('A4', 'landscape');

            $this->business->notify(new SendFile('Your Exported Orders', [
                'Please find attached your exported orders from ' . $fromDate . ' to ' . $toDate,
            ], ($fromDate . ' - ' . $toDate), $pdf->output(), $this->data['docType']));
        }
    }
}
