<?php

namespace App\Actions\Business\Settings\UserManagement\Settings;

use Illuminate\Support\Facades;

class Update extends Action
{
    /**
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function process(): array
    {
        $rules['key'] = 'required';
        $rules['value'] = 'required|boolean';

        $data = Facades\Validator::validate($this->data, $rules);

        $this->businessSettings->key = $data['key'];
        $this->businessSettings->value = $data['value'];
        $this->businessSettings->business_id = $this->business->getKey();
        $this->businessSettings->save();

        return Retrieve::withBusiness($this->business)->process();
    }
}
