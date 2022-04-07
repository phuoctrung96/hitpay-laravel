<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class ComplianceRiskLevel extends Enumeration
{
    const HIGH_RISK = 'high_risk';

    const MEDIUM_RISK = 'medium_risk';

    const LOW_RISK = 'low_risk';

    public static function getList()
    {
        return [
            self::LOW_RISK => 'Low Risk',
            self::MEDIUM_RISK => 'Medium Risk',
            self::HIGH_RISK => 'High Risk',
        ];
    }

}
