<?php


namespace App\Enumerations;


class VerificationStatus extends Enumeration
{
    const VERIFIED = 'myinfo_verified';
    const PENDING = 'pending';
    const MANUAL_VERIFIED = 'manual_verified';
    const REJECTED = 'rejected';
}
