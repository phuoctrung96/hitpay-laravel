<?php

namespace App\Actions\Business\EmailTemplates;

use App\Business;
use App\Business\EmailTemplate;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades;

class SetDefault extends Action
{
    /**
     * @return EmailTemplate
     * @throws \Exception
     */
    public function process(): EmailTemplate
    {
        if (!$this->business instanceof Business) {
            throw new \Exception("Business not yet set.");
        }

        $this->validate();

        $businessEmailTemplates = $this->business->emailTemplate()->first();

        if (!$businessEmailTemplates instanceof EmailTemplate) {
            throw new \Exception("This business not have email template.");
        }

        $templateFor = $this->data['template_for'];
        unset($this->data['template_for']);

        if ($templateFor === self::ORDER_CONFIRMATION_TEMPLATE) {
            $businessEmailTemplates->order_confirmation_template = self::DEFAULT_EMAIL[self::ORDER_CONFIRMATION_TEMPLATE];
        } elseif ($templateFor === self::PAYMENT_RECEIPT_TEMPLATE) {
            $businessEmailTemplates->payment_receipt_template = self::DEFAULT_EMAIL[self::PAYMENT_RECEIPT_TEMPLATE];
        } elseif ($templateFor === self::INVOICE_RECEIPT_TEMPLATE) {
            $businessEmailTemplates->invoice_receipt_template = self::DEFAULT_EMAIL[self::INVOICE_RECEIPT_TEMPLATE];
        } elseif ($templateFor === self::MOBILE_PRINTER_TEMPLATE) {
            $businessEmailTemplates->mobile_printer_template = self::DEFAULT_EMAIL[self::MOBILE_PRINTER_TEMPLATE];
        } elseif ($templateFor === self::RECURRING_INVOICE_TEMPLATE) {
            $businessEmailTemplates->recurring_invoice_template = self::DEFAULT_EMAIL[self::RECURRING_INVOICE_TEMPLATE];
        }

        $businessEmailTemplates->save();

        return $businessEmailTemplates;
    }

    /**
     * @return void
     * @throws ValidationException
     */
    private function validate(): void
    {
        $rules = [
            'template_for' => 'required|string|in:' . implode(",", $this->getTemplateAvailable())
        ];

        Facades\Validator::make($this->data, $rules)->validate();
    }
}
