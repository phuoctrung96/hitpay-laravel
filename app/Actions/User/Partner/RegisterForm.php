<?php

namespace App\Actions\User\Partner;

use App\Actions\User\UserInfoByIp;
use App\Business\BusinessCategory;

class RegisterForm extends Action
{
    use UserInfoByIp;

    /**
     * @return array
     */
    public function process(): array
    {
        return [
            'countries' => $this->getDefaultCountries(),
            'business_categories' => BusinessCategory::all(),
        ];
    }
}
