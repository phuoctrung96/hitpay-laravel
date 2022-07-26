<?php

namespace App\Actions\Business\Settings\UserManagement\Settings;

use App\Business\BusinessSettings;

class Retrieve extends Action
{
    /**
     * @return array
     */
    public function process(): array
    {
        $businessSettings = $this->business->settings()->get();

        if ($businessSettings->count() === 0) {
            return \App\Enumerations\Business\BusinessSettings::getDefault();
        }

        return $businessSettings->toArray();
    }
}
