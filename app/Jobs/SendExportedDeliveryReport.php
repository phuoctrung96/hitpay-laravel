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

class SendExportedDeliveryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    public $data;

    public $pickup;

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

        $status = OrderStatus::REQUIRES_BUSINESS_ACTION;

        $orders->where('status', $status);

        if ($this->data['pickup']) $orders->where('customer_pickup', 1);
        else $orders->where('customer_pickup', 0);

        $fromDelDate = Date::parse($this->data['delivery_start']);
        $toDelDate = Date::parse($this->data['delivery_end']);

        if ($fromDelDate->gt($toDelDate)) {
            [$fromDelDate, $toDelDate] = [$toDelDate, $fromDelDate];
        }

        $orders->whereNotNull('slot_date');

        $orders->whereDate('slot_date', '>=', $fromDelDate->startOfDay()->toDateTimeString());
        $orders->whereDate('slot_date', '<=', $toDelDate->endOfDay()->toDateTimeString());

        $orders = $orders->orderBy('created_at')->get();

        $data = [];
        $i = 0;

        /** @var \App\Business\Order $order */
        foreach ($orders as $order) {
            $orderedProducts = Collection::make();

            foreach ($order->products as $product) {
                $name = $product->name;

                $name .= ' (Quantity: ' . $product->quantity . ')';

                $orderedProducts->add($name);
            }

            $orderStatus = 'Pending';
            $slotTime = json_decode($order->slot_time);
            $slotTime = $slotTime->from.' - '.$slotTime->to;

            $singleData = [
                $i++,
                $order->id,
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
                $order->remark,
                $order->customer_pickup ? 'Yes' : 'No',
                $order->created_at->toDateTimeString(),
                $order->slot_date,
                $slotTime
            ];

            $data[] = $singleData;
        }

        if ($this->data['docType'] == 'csv') {
            $csv = Writer::createFromString('');

            $csv->insertOne([
                '#',
                'ID',
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
                'Buyer remarks',
                'Pickup',
                'Ordered Date',
                'Slot Date',
                'Slot Time'
            ]);

            $i = 1;

            $csv->insertAll($data);

            $this->business->notify(new SendFile('Your Delivery Report', [
                'Please find attached your delivery report from ' . $fromDelDate . ' to ' . $toDelDate,
            ], ($fromDelDate . ' - ' . $toDelDate), $csv->getContent(), $this->data['docType']));
        } elseif ($this->data['docType'] == 'pdf') {
            $vars['data'] = $data;
            $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('hitpay-email.pdf.export-delivery', $vars);
            $pdf->setPaper('A4', 'landscape');

            $this->business->notify(new SendFile('Your Delivery Report', [
                'Please find attached your delivery report from ' . $fromDelDate . ' to ' . $toDelDate,
            ], ($fromDelDate . ' - ' . $toDelDate), $pdf->output(), $this->data['docType']));
        }
    }
}
