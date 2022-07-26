<?php

namespace App\Actions\Business\EmailTemplates;

use App\Business;
use App\Business\EmailTemplate;

class StoreDefault extends Action
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

        $businessEmailTemplates = $this->business->emailTemplate()
            ->where('business_id', $this->business->getKey())->first();

        if (!$businessEmailTemplates instanceof EmailTemplate) {
            $businessEmailTemplates = new EmailTemplate();
            $businessEmailTemplates->business_id = $this->business->getKey();
            $businessEmailTemplates->common_customisation = [];
            $businessEmailTemplates->order_confirmation_template = self::DEFAULT_EMAIL[self::ORDER_CONFIRMATION_TEMPLATE];
            $businessEmailTemplates->payment_receipt_template = self::DEFAULT_EMAIL[self::PAYMENT_RECEIPT_TEMPLATE];
            $businessEmailTemplates->invoice_receipt_template = self::DEFAULT_EMAIL[self::INVOICE_RECEIPT_TEMPLATE];
            $businessEmailTemplates->mobile_printer_template = self::DEFAULT_EMAIL[self::MOBILE_PRINTER_TEMPLATE];
            $businessEmailTemplates->recurring_invoice_template = self::DEFAULT_EMAIL[self::RECURRING_INVOICE_TEMPLATE];
            $businessEmailTemplates->action_button_text = [];
            $businessEmailTemplates->action_button_text_color = [];
            $businessEmailTemplates->action_button_background_color = [];
            $businessEmailTemplates->save();
        }

        return $businessEmailTemplates;
    }
}
