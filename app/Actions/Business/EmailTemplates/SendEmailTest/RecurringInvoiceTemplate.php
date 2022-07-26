<?php

namespace App\Actions\Business\EmailTemplates\SendEmailTest;

use App\Business;
use Illuminate\Support\Facades;

class RecurringInvoiceTemplate extends Action
{
    /**
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function process(): void
    {
        if (!$this->business instanceof Business) {
            throw new \Exception("Business not set");
        }

        if ($this->emailTemplateData === null) {
            throw new \Exception("No have email template set");
        }

        $this->validateEmailTemplateData();

        $emailTemplateDataConverted = $this->convertEmailTemplateData();

        $notification = new \App\Notifications\Business\EmailTemplates\RecurringInvoiceTemplate($this->business, $emailTemplateDataConverted);

        $this->business->notify($notification);
    }

    /**
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateEmailTemplateData(): void
    {
        $rules['email_subject'] = 'required|string';
        $rules['title'] = 'required|string';
        $rules['subtitle'] = 'required|string';
        $rules['footer'] = 'required|string';
        $rules['button_text'] = 'required|string';
        $rules['button_background_color'] = 'required|string';
        $rules['button_text_color'] = 'required|string';

        Facades\Validator::make($this->emailTemplateData, $rules)->validate();
    }
}
