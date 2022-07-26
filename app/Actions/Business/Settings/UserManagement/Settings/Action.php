<?php
namespace App\Actions\Business\Settings\UserManagement\Settings;

use App\Actions\Business\Action as BaseAction;
use App\Business;
use App\Business\BusinessSettings as Model;
use Exception;

abstract class Action extends BaseAction
{
    protected ?Model $businessSettings = null;

    protected ?string $businessSettingId = null;

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function business(Business $business) : self
    {
        if ($this->businessSettings && $this->businessSettings->business->getKey() !== $business->getKey()) {
            throw new Exception("The business (ID : $business->getKey()) has no right to the business settings (ID : $this->businessSettingId)");
        }

        return parent::business($business);
    }

    /**
     * @param Model $businessSettings
     * @return $this
     * @throws Exception
     */
    public function setBusinessSetting(Business\BusinessSettings $businessSettings) : self
    {
        if ($this->business && $this->business->getKey() !== $businessSettings->business->getKey()) {
            throw new Exception("The business (ID : {$this->business->getKey()}) has no right to the business settings (ID : {$businessSettings->getKey()})");
        }

        $this->businessSettings = $businessSettings;

        return $this;
    }
}
