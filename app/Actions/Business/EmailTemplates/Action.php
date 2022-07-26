<?php

namespace App\Actions\Business\EmailTemplates;

use App\Actions\Business\Action as BaseAction;
use Illuminate\Support\Str;

abstract class Action extends BaseAction
{
    const PAYMENT_RECEIPT_TEMPLATE = "payment_receipt_template";
    const ORDER_CONFIRMATION_TEMPLATE = "order_confirmation_template";
    const RECURRING_INVOICE_TEMPLATE = "recurring_invoice_template";
    const INVOICE_RECEIPT_TEMPLATE = "invoice_receipt_template"; // not yet applied
    const MOBILE_PRINTER_TEMPLATE = "mobile_printer_template"; // not yet applied

    const DEFAULT_EMAIL = [
        self::PAYMENT_RECEIPT_TEMPLATE => [
            'email_subject' => 'Your Receipt from {{business_name}}',
            'title' => '{{business_name}}',
            'subtitle' => 'View transaction details below',
            'footer' => 'Notice something wrong?<a href="mailto:{{business_email}}" target="_blank" style="color: #3498db; text-decoration: underline;">Contact our support team</a> and we will be happy to help.'
        ],
        self::ORDER_CONFIRMATION_TEMPLATE => [
            'email_subject' => 'Thank You For Your Order',
            'title' => '{{business_name}}',
            'subtitle' => 'View Order Details Below',
            'footer' => 'Notice something wrong?<a href="mailto:{{business_email}}" target="_blank" style="color: #3498db; text-decoration: underline;">Contact our support team</a> and we will be happy to help.',
            'store_information_title' => 'Seller Information',
            'store_information_value' => '{{business_name}} ({{business_email}})'
        ],
        self::RECURRING_INVOICE_TEMPLATE => [
            'email_subject' => 'New Recurring Payment Invoice from {{business_name}}',
            'title' => '{{business_name}}',
            'subtitle' => 'New Recurring Payment Invoice from {{business_name}}',
            'footer' => 'If you have any questions about this invoice, please contact <a href="mailto:{{business_email}}" target="_blank" style="color: #3498db; text-decoration: underline;">{{business_email}}</a>',
            'button_text' => 'Click here to view this invoice',
            'button_background_color' => '#002771',
            'button_text_color' => '#FFFFFF',
        ],
        self::INVOICE_RECEIPT_TEMPLATE => [
            'email_subject' => 'Your Invoice from {{business_name}}',
            'title' => '{{business_name}}',
            'subtitle' => 'View invoice details below',
            'footer' => 'Notice something wrong?<a href="mailto:{{business_email}}" target="_blank" style="color: #3498db; text-decoration: underline;">Contact our support team</a> and we will be happy to help.'
        ],
        self::MOBILE_PRINTER_TEMPLATE => [
            'email_subject' => 'Your Invoice from {{business_name}}',
            'title' => '{{business_name}}',
            'subtitle' => 'View invoice details below',
            'footer' => 'Notice something wrong?<a href="mailto:{{business_email}}" target="_blank" style="color: #3498db; text-decoration: underline;">Contact our support team</a> and we will be happy to help.'
        ],
    ];

    protected array $emailTemplateData;

    protected ?\App\Business\Charge $charge = null;

    /**
     * @param array $emailTemplate
     * @return \App\Actions\Business\EmailTemplates\SendEmailTest\Action
     */
    public function setEmailTemplateData(array $emailTemplate): self
    {
        $this->emailTemplateData = $emailTemplate;

        return $this;
    }

    public function setCharge(\App\Business\Charge $charge): self
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * @return array
     */
    protected function convertEmailTemplateData(): array
    {
        $convertResult = [];

        $variableTemplate = $this->getVariableTemplate();

        $emailTemplateData = $this->emailTemplateData;

        if (isset($emailTemplateData['template_for'])) {
            unset($emailTemplateData['template_for']);
        }

        foreach ($emailTemplateData as $key => $emailTemplateItem) {
            $emailTemplateItemResult = $emailTemplateItem;

            foreach ($variableTemplate as $keyVariableTemplateItem => $variableTemplateItem) {
                if (Str::contains($emailTemplateItemResult, $keyVariableTemplateItem)) {
                    $emailTemplateItemResult = Str::replaceArray($keyVariableTemplateItem, [$variableTemplateItem], $emailTemplateItemResult);
                }
            }

            $convertResult[$key] = $emailTemplateItemResult;
        }

        return $convertResult;
    }

    protected function getVariableTemplate(): array
    {
        $chargeId = '94bdebb0-b009-482b-953f-99a48391ff8d'; // random for example
        $chargeDate = '05.06.2022'; // random for example

        if ($this->charge instanceof \App\Business\Charge) {
            $chargeId = $this->charge->getKey();
            $chargeDate = $this->charge->closed_at->toDateTimeString();
        }

        return [
            '{{business_name}}' => $this->business->name,
            '{{business_email}}' => $this->business->email,
            '{{business_phone_number}}' => $this->business->phone ?? '',
            '{{charge_id}}' => $chargeId,
            '{{charge_date}}' => $chargeDate,
        ];
    }

    /**
     * @return array
     */
    protected function getTemplateAvailable(): array
    {
        return array_keys(self::DEFAULT_EMAIL);
    }
}
