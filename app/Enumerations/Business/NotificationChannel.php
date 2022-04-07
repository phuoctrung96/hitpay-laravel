<?php

namespace App\Enumerations\Business;

use App\Enumerations\Enumeration;

class NotificationChannel extends Enumeration
{
    const EMAIL = 'email';

    const PUSH_NOTIFICATION = 'push_notification';
}
