<?php

namespace App\Enumerations\Business\Wallet;

use MyCLabs\Enum\Enum;

class Event extends Enum
{
    const ADMINISTRATIVE_DEDUCTION = 'administrative_deduction';

    const ADMINISTRATIVE_TOP_UP = 'administrative_top_up';

    const PAID_TO_BANK = 'paid_to_bank';

    const RECEIVED_FROM_CHARGE = 'received_from_charge';

    const CONFIRMED_CHARGE = 'confirmed_charge';

    const RECEIVED_FROM_WALLET = 'received_from_wallet';

    const TRANSFERRED_TO_WALLET = 'transferred_to_wallet';

    const TOP_UP = 'top_up';

    const WITHDREW_FOR_CHARGEBACK = 'withdrew_for_chargeback';

    const WITHDREW_FOR_REFUND = 'withdrew_for_refund';
}
