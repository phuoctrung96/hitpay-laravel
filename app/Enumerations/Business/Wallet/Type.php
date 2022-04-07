<?php

namespace App\Enumerations\Business\Wallet;

use MyCLabs\Enum\Enum;

class Type extends Enum
{
    const AVAILABLE = 'available';

    const DEPOSIT = 'deposit';

    const PENDING = 'pending';

    const RESERVE = 'reserve';
}
