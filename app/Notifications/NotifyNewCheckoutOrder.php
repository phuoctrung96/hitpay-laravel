<?php

namespace App\Notifications;

use App\Business\Order;
use App\Business\TaxSetting;
use App\Enumerations\Business\Channel as BusinessChannel;
use App\Enumerations\Business\Event;
use HitPay\Firebase\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class NotifyNewCheckoutOrder extends Notification implements ShouldQueue
{
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return $this->getVia($notifiable, Event::NEW_ORDER);
    }

    /**
     * @param \App\Business $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     * @throws \ReflectionException
     */
    public function toMail($notifiable)
    {
        $title = $this->order->is_shippable ? 'You have a shipping order' : 'You have a successful incoming order';
        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        $orderedProducts = $this->order->products->toArray();

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

        $tax_setting = TaxSetting::find($this->order->tax_setting_id);
        $tax_amount = 0;
        if($tax_setting)
            $tax_amount = $this->order->amount * $tax_setting->rate / 100;

        return (new MailMessage)->view('hitpay-email.new-order', [
                'title' => $title,
                'business_logo' => $notifiable->logo ? $notifiable->logo->getUrl() : asset('hitpay/logo-000036.png'),
                'business_name' => $notifiable->name,
                'business_email' => $notifiable->email,
                'business_address' => $notifiable->getAddress(),
                'order_id' => $this->order->getKey(),
                'shipping' => [
                    'method' => $this->order->shipping_method ?? 'Shipping',
                    'amount' => getFormattedAmount($this->order->currency, $this->order->shipping_amount),
                ],
                'discount' => [
                    'name' => $this->order->automatic_discount_reason ?? 'Discount',
                    'amount' => getFormattedAmount($this->order->currency, $this->order->automatic_discount_amount + $this->order->additional_discount_amount),
                ],
                'coupon_amount' => getFormattedAmount($this->order->currency, $this->order->coupon_amount),
                'sub_total_amount' => getFormattedAmount($this->order->currency, $this->order->amount),
                'tax_amount' => getFormattedAmount($this->order->currency, $tax_amount),
                'order_amount' => getFormattedAmount($this->order->currency, $this->order->amount + $tax_amount),
                'order_date' => $this->order->created_at->toDateTimeString(),
                'order_remark' => $this->order->remark,
                'ordered_products' => $orderedProducts,
                'customer' => [
                    'name' => $this->order->customer_name,
                    'email' => $this->order->customer_email,
                    'address' => $this->order->display('customer_address'),
                ],
            ] + ($shipping ?? []))->subject($title);
    }

    public function toFirebase($notifiable)
    {
        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        return new Message($prefix.'You have received an online order',
            'View transaction and order details under Orders in the web dashboard or mark order as completed in the app under Products > Pending orders');
    }
}
