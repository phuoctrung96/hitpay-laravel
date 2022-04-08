<?php

namespace App\Notifications;

use App\Business\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class NotifyOrderVoided extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return [
            'mail',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param \App\Business\Order $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     * @throws \ReflectionException
     */
    public function toMail($notifiable)
    {
        $chargeModel = $this->order->charges->first();
        $business = $this->order->business;
        $orderedProducts = $this->order->products->toArray();

        if ($chargeModel) {
            if ($chargeModel->payment_provider_charge_method === 'card_present') {
                $application = [
                    'application' => [
                        'identifier' => $chargeModel->data['payment_method_details']['card_present']['receipt']['dedicated_file_name'],
                        'name' => $chargeModel->data['payment_method_details']['card_present']['receipt']['application_preferred_name'],
                    ],
                ];
            }
        }

        /**
         * @var \App\Business\Order $this ->order
         */

        foreach ($orderedProducts as $key => $value) {
            $orderedProducts[$key]['unit_price'] = getFormattedAmount($this->order->currency, $value['unit_price']);
            $orderedProducts[$key]['price'] = getFormattedAmount($this->order->currency, $value['price']);
        }

        if (!$this->order->customer_pickup) {
            $shipping = [
                'shipping' => [
                    'method' => $this->order->shipping_method,
                    'amount' => getFormattedAmount($this->order->currency, $this->order->shipping_amount),
                ],
            ];
        }

        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        return (new MailMessage)->view('hitpay-email.buyer-order-voided', [
                'charge_id' => $chargeModel->id,
                'business_logo' => $business->logo ? $business->logo->getUrl() : asset('hitpay/logo-000036.png'),
                'business_name' => $business->name,
                'business_email' => $business->email,
                'business_address' => $business->getAddress(),
                'order_id' => $this->order->getKey(),
                'shipping' => [
                    'method' => $this->order->shipping_method ?? 'Shipping',
                    'amount' => getFormattedAmount($this->order->currency, $this->order->shipping_amount),
                ],
                'discount' => [
                    'name' => $this->order->automatic_discount_name ?? 'Discount',
                    'amount' => getFormattedAmount($this->order->currency, $this->order->automatic_discount_amount),
                ],
                'coupon_amount' => getFormattedAmount($this->order->currency, $this->order->coupon_amount),
                'order_amount' => getFormattedAmount($this->order->currency, $this->order->amount),
                'order_date' => $this->order->created_at->toDateTimeString(),
                'voided_date' => $this->order->closed_at->toDateTimeString(),
                'ordered_products' => $orderedProducts,
                'customer' => [
                    'name' => $this->order->customer_name,
                    'email' => $this->order->customer_email,
                    'address' => $this->order->display('customer_address'),
                ],
            ] + ($shipping ?? []) + ($application ?? []))->subject($prefix.'Thank you for your order');
    }
}
