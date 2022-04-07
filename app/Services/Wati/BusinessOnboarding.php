<?php

namespace App\Services\Wati;

use App\Business;
use App\Services\Wati\Api\Contact;
use App\Services\Wati\Api\TemplateMessage;

class BusinessOnboarding
{
    protected $business;

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    public function onboard()
    {
        // create contact
        $watiContact = new Contact($this->business);
        $watiContact->create();

        // send message template
        $templateMessage = 'welcome_to_hitpay';
        $onboardMessage = 'welcome_to_hitpay';

        $watiTemplateMessage = new TemplateMessage($this->business);
        $watiTemplateMessage->send($templateMessage, $onboardMessage);
    }
}
