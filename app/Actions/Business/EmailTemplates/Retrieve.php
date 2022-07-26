<?php

namespace App\Actions\Business\EmailTemplates;

use App\Business;
use App\Business\EmailTemplate;
use App;

class Retrieve extends Action
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

        $businessEmailTemplates = $this->business->emailTemplate()->first();

        if (!$businessEmailTemplates instanceof EmailTemplate) {
            App::abort(404, "Business not have email template");
        }

        return $businessEmailTemplates;
    }
}
