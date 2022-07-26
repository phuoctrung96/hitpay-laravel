<?php

namespace App\Notifications;

use App\Actions\Business\EmailTemplates\ConvertEmailTemplate;
use App\Business\EmailTemplate;
use App\Business\Order;
use App\Business\TaxSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class NotifyOrderConfirmation extends Notification implements ShouldQueue
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
                $card = $chargeModel->getDataStripeCard();

                $application = [
                    'application' => [
                        'identifier' => $card['receipt']['dedicated_file_name'],
                        'name' => $card['receipt']['application_preferred_name'],
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

        $shippingAmount = 0;

        if (!$this->order->customer_pickup) {
            $shippingAmount = $this->order->shipping_amount;

            $shipping = [
                'shipping' => [
                    'method' => $this->order->shipping_method,
                    'amount' => getFormattedAmount($this->order->currency, $shippingAmount),
                ],
            ];
        }

        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        $tax_setting = TaxSetting::find($this->order->tax_setting_id);
        $tax_exception_amount = $this->order->automatic_discount_amount + $this->order->additional_discount_amount + $this->order->coupon_amount;
        if($tax_setting) {
            $tax_amount = ($this->order->line_item_price - $tax_exception_amount) * (float) $tax_setting->rate / 100;
        } else {
            $tax_amount = 0;
        }

        $sub_total = ($this->order->line_item_price + $shippingAmount) - $tax_exception_amount;

        $isHaveTemplateEmail = false;

        $businessEmailTemplate = $business->emailTemplate()->first();

        if ($businessEmailTemplate instanceof EmailTemplate) {
            $isHaveTemplateEmail = true;
        }

        $title = null;
        $subtitle = null;
        $footer = null;
        $storeInformationTitle = null;
        $storeInformationValue = null;

        if ($isHaveTemplateEmail) {
            $emailTemplate = ConvertEmailTemplate::withBusiness($business)
                ->setEmailTemplateData($businessEmailTemplate->order_confirmation_template)
                ->process();

            $emailSubject = $emailTemplate['email_subject'] ?? null;

            if ($emailSubject === null) {
                $emailSubject = 'Thank you for your order';
            }

            $emailSubject = $prefix . $emailSubject;

            $title = $emailTemplate['title'] ?? null;
            $subtitle = $emailTemplate['subtitle'] ?? null;
            $footer = $emailTemplate['footer'] ?? null;
            $storeInformationTitle = $emailTemplate['store_information_title'];
            $storeInformationValue = $emailTemplate['store_information_value'];
        } else {
            $emailSubject = $prefix.'Thank you for your order';
        }

        return (new MailMessage)->view('hitpay-email.buyer-order-confirmation', [
                'charge_id' => $chargeModel ? $chargeModel->id : '',
                'business_logo' => $business->logo ? $business->logo->getUrl() : asset('hitpay/logo-000036.png'),
                'business_name' => $business->name,
                'business_email' => $business->email,
                'business_address' => $business->getAddress(),
                'order_id' => $this->order->getKey(),
                'shipping' => [
                    'method' => $this->order->customer_pickup ? "Self Pickup" :$this->order->shipping_method,
                    'amount' => getFormattedAmount($this->order->currency, $this->order->shipping_amount),
                ],
                'discount' => [
                    'name' => $this->order->automatic_discount_name ?? 'Discount',
                    'amount' => getFormattedAmount($this->order->currency, $this->order->automatic_discount_amount + $this->order->additional_discount_amount),
                ],
                'coupon_amount' => getFormattedAmount($this->order->currency, $this->order->coupon_amount),
                'sub_total_amount' => getFormattedAmount($this->order->currency, $sub_total),
                'tax_amount' => getFormattedAmount($this->order->currency, $tax_amount),
                'order_amount' => getFormattedAmount($this->order->currency, $sub_total + $tax_amount),
                'order_date' => $this->order->created_at->toDateTimeString(),
                'order_remark' => $this->order->remark,
                'ordered_products' => $orderedProducts,
                'customer' => [
                    'name' => $this->order->customer_name,
                    'email' => $this->order->customer_email,
                    'address' => $this->order->display('customer_address'),
                ],
                'isHaveTemplateEmail' => $isHaveTemplateEmail,
                'title' => $title,
                'subtitle' => $subtitle,
                'footer' => $footer,
                'store_information_title' => $storeInformationTitle,
                'store_information_value' => $storeInformationValue,
            ] + ($shipping ?? []) + ($application ?? []))->subject($emailSubject);
    }
}
