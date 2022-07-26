<?php

namespace App\Actions\Business\Settings\UserManagement\Settings;

use App\Business\BusinessSettings;
use Illuminate\Support\Facades;

class Store extends Action
{
    /**
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function process(): array
    {
        $rules['key'] = 'required';
        $rules['value'] = 'required';

        $data = Facades\Validator::validate($this->data, $rules);

        $businessSettings = $this->business->settings()->where('key', $data['key'])->first();

        if (!$businessSettings instanceof BusinessSettings) {
            $businessSettings = new BusinessSettings();
        }

        $businessSettings->key = $data['key'];
        $businessSettings->value = $data['value'];
        $businessSettings->business_id = $this->business->getKey();
        $businessSettings->save();

        return Retrieve::withBusiness($this->business)->process();
    }
}
