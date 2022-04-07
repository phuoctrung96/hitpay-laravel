<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

/**
 * Class InvoiceStatus
 * @package App\Enumerations\Business
 */
class InvoiceStatus extends Enumeration
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const PAID = 'paid';

    public const ALL = 'all';

    public const OVERDUE = 'overdue';

    public const DRAFT = 'draft';

    public const PARTIALITY_PAID = 'partiality_paid';
}
