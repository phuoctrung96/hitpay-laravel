<?php

namespace App\Actions\Business\EmailTemplates;

use App\Business;
use App\Business\EmailTemplate;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades;
use App;

class SendEmailTest extends Action
{
    /**
     * @return Boolean
     * @throws \Exception
     */
    public function process(): bool
    {
        if (!$this->business instanceof Business) {
            throw new \Exception("Business not yet set.");
        }

        $businessEmailTemplates = $this->business->emailTemplate()->first();

        if (!$businessEmailTemplates instanceof EmailTemplate) {
            App::abort(404, "Business not have email template");
        }

        $this->validate();

        $templateFor = $this->data['template_for'];

        if (isset($this->data['footer'])) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', Facades\App::bootstrapPath('cache/serializer_path'));
            $purifier = new \HTMLPurifier($config);
            $this->data['footer'] = $purifier->purify($this->data['footer']);
        }

        if ($templateFor === self::PAYMENT_RECEIPT_TEMPLATE) {
            App\Actions\Business\EmailTemplates\SendEmailTest\PaymentReceiptTemplate::withBusiness($this->business)
                ->setEmailTemplateData($this->data)->process();
        }

        if ($templateFor === self::ORDER_CONFIRMATION_TEMPLATE) {
            App\Actions\Business\EmailTemplates\SendEmailTest\OrderConfirmationTemplate::withBusiness($this->business)
                ->setEmailTemplateData($this->data)->process();
        }

        if ($templateFor === self::RECURRING_INVOICE_TEMPLATE) {
            App\Actions\Business\EmailTemplates\SendEmailTest\RecurringInvoiceTemplate::withBusiness($this->business)
                ->setEmailTemplateData($this->data)->process();
        }

        return true;
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
