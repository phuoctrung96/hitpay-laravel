<?php

namespace App\Enumerations;

class OnboardingStatus extends Enumeration
{
    const PENDING_SUBMISSION = 'pending_submission';

    const PENDING_VERIFICATION = 'pending_verification';

    const SUCCESS = 'success';

    const REJECTED = 'rejected';
}
