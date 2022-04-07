<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\SubscriptionPlan;
use App\Business\RecurringPlanRenewalCycle as RenewalCycle;
use Illuminate\Http\Request;

class RecurringPlanRepository
{

    public static function store(Request $request, Business $business) : SubscriptionPlan
    {
    }

    public static function update(Request $request, SubscriptionPlan $recurringPlan) : SubscriptionPlan
    {
    }

    public static function delete(SubscriptionPlan $recurringPlan) : SubscriptionPlan
    {
    }
}
