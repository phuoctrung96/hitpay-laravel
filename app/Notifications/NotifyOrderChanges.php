<?php

namespace App\Notifications;

use App\Business\Order;
use App\Business\TaxSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class NotifyOrderChanges extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public $message;

    public $isFulfilled;

    /**
     * NotifyOrderChanges constructor.
     *
     * @param array $message
     * @param bool $isFulfilled
     */
    public function __construct(Order $order, ?string $message, bool $isFulfilled)
    {
        $this->order = $order;
        $this->message = $message;
        $this->isFulfilled = $isFulfilled;
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
     * @param \App\Business\Order $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     * @throws \ReflectionException
     */
    public function toMail($notifiable)
    {
        $title = $this->isFulfilled ? 'Your order has been shipped' : 'Your order has been updated';
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
        

        return (new MailMessage)->view('hitpay-email.buyer-order-update', [
                'title' => $title,
                'messageString' => $this->message,
                'business_logo' => $notifiable->logo ? $notifiable->logo->getUrl() : asset('hitpay/logo-000036.png'),
                'business_name' => $notifiable->business->name,
                'business_email' => $notifiable->business->email,
                'business_address' => $notifiable->display('customer_address'),
                'order_id' => $this->order->getKey(),
                'order_completed' => $this->order->isCompleted(),
                'shipping' => [
                    'method' => $this->order->shipping_method ?? 'Shipping',
                    'amount' => getFormattedAmount($this->order->currency, $this->order->shipping_amount),
                ],
                'discount' => [
                    'name' => $this->order->automatic_discount_name ?? 'Discount',
                    'amount' => getFormattedAmount($this->order->currency, $this->order->automatic_discount_amount),
                ],
                'coupon_amount' => getFormattedAmount($this->order->currency, $this->order->coupon_amount),
                'sub_total_amount' => getFormattedAmount($this->order->currency, $this->order->amount),
                'tax_amount' => getFormattedAmount($this->order->currency, $tax_amount),
                'order_amount' => getFormattedAmount($this->order->currency, $this->order->amount + $tax_amount),
                'order_date' => $this->order->created_at->toDateTimeString(),
                'ordered_products' => $orderedProducts,
                'customer' => [
                    'name' => $this->order->customer_name,
                    'email' => $this->order->customer_email,
                    'address' => $this->order->display('customer_address'),
                ],
            ] + ($shipping ?? []))->subject($title);
    }
}
