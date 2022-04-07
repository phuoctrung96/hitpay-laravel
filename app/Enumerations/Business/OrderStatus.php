<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class OrderStatus extends Enumeration
{
    const CANCELED = 'canceled';

    const COMPLETED = 'completed';

    const DRAFT = 'draft';

    const EXPIRED = 'expired';

    const REQUIRES_BUSINESS_ACTION = 'requires_business_action';

    const REQUIRES_CUSTOMER_ACTION = 'requires_customer_action';

    const REQUIRES_PAYMENT_METHOD = 'requires_payment_method';

    const REQUIRES_POINT_OF_SALES_ACTION = 'requires_point_of_sales_action';
}
