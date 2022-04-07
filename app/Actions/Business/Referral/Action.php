<?php

namespace App\Actions\Business\Referral;

use App\Actions\Business\Action as BaseAction;
use Illuminate\Support\Facades;

abstract class Action extends BaseAction
{
    protected string $emailInvitation;

    /**
     * @param string $email
     * @return $this
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setEmailInvitation(string $email) : self
    {
        $rules = [
            'email' => 'required|email'
        ];

        Facades\Validator::make(['email' => $email], $rules)->validate();

        $this->emailInvitation = $email;

        return $this;
    }
}
