<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringPlanRenewalCycle extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_recurring_plan_renewal_cycles';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'int',
        'active' => 'bool',
    ];

    /**
     * Get the recurring plan of the renewal cycle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Business\SubscriptionPlan
     */
    public function recurringPlan() : BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'business_recurring_plan_id', 'id', 'recurring_plan');
    }
}
