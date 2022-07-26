<?php

namespace App\Actions\Business\EmailTemplates;

use App\Business;

class ConvertEmailTemplate extends Action
{
    /**
     * @return array
     * @throws \Exception
     */
    public function process(): array
    {
        if (!$this->business instanceof Business) {
            throw new \Exception("Business not set");
        }

        if ($this->emailTemplateData === null) {
            throw new \Exception("No have email template set");
        }

        return $this->convertEmailTemplateData();
    }
}
