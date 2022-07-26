<?php

namespace App\Actions\Business\EmailTemplates;

use App\Business;
use App\Business\EmailTemplate;
use Illuminate\Support\Facades;
use Illuminate\Validation\ValidationException;

class Update extends Action
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

        if (isset($this->data['footer'])) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', Facades\App::bootstrapPath('cache/serializer_path'));
            $purifier = new \HTMLPurifier($config);
            $this->data['footer'] = $purifier->purify($this->data['footer']);
        }

        if ($templateFor === self::ORDER_CONFIRMATION_TEMPLATE) {
            $businessEmailTemplates->order_confirmation_template = $this->data;
        } elseif ($templateFor === self::PAYMENT_RECEIPT_TEMPLATE) {
            $businessEmailTemplates->payment_receipt_template = $this->data;
        } elseif ($templateFor === self::INVOICE_RECEIPT_TEMPLATE) {
            $businessEmailTemplates->invoice_receipt_template = $this->data;
        } elseif ($templateFor === self::MOBILE_PRINTER_TEMPLATE) {
            $businessEmailTemplates->mobile_printer_template = $this->data;
        } elseif ($templateFor === self::RECURRING_INVOICE_TEMPLATE) {
            $businessEmailTemplates->recurring_invoice_template = $this->data;
        }

        $businessEmailTemplates->save();

        return $businessEmailTemplates->refresh();
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

        // set rules each template if later have other field set
        if ($this->data['template_for'] === self::ORDER_CONFIRMATION_TEMPLATE) {
            $rules['email_subject'] = 'required|string';
            $rules['title'] = 'required|string';
            $rules['subtitle'] = 'required|string';
            $rules['footer'] = 'required|string';
        } elseif ($this->data['template_for'] === self::PAYMENT_RECEIPT_TEMPLATE) {
            $rules['email_subject'] = 'required|string';
            $rules['title'] = 'required|string';
            $rules['subtitle'] = 'required|string';
            $rules['footer'] = 'required|string';
        } elseif ($this->data['template_for'] === self::INVOICE_RECEIPT_TEMPLATE) {
            $rules['email_subject'] = 'required|string';
            $rules['title'] = 'required|string';
            $rules['subtitle'] = 'required|string';
            $rules['footer'] = 'required|string';
        } elseif ($this->data['template_for'] === self::MOBILE_PRINTER_TEMPLATE) {
            $rules['email_subject'] = 'required|string';
            $rules['title'] = 'required|string';
            $rules['subtitle'] = 'required|string';
            $rules['footer'] = 'required|string';
        }

        Facades\Validator::make($this->data, $rules)->validate();
    }
}
