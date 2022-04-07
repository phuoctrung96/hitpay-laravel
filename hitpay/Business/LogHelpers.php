<?php

namespace HitPay\Business;

use App\Business\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait LogHelpers
{
    /**
     * Get the related business logs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany|\App\Business\Log|\App\Business\Log[]
     */
    public function logs() : MorphMany
    {
        return $this->morphMany(Log::class, 'log', 'associable_type', 'associable_id', 'id');
    }

    /**
     * Create log for the business's activity.
     *
     * @param string $group
     * @param string $event
     * @param \Illuminate\Support\Carbon $loggedAt
     * @param array|null $attributes
     * @param \Illuminate\Database\Eloquent\Model|null $relatable
     *
     * @throws \Exception
     */
    protected function createLog(
        string $group, string $event, Carbon $loggedAt, array $attributes = null, Model $relatable = null
    ) : void {
        $log = Log::make([
            'business_id' => $this->getBusinessId(),
            'group' => $group,
            'event' => $event,
            'logged_at' => $loggedAt,
        ]);

        if ($this instanceof Model) {
            $log->associable()->associate($this);
        }

        if ($attributes) {
            $log->logAttributes($attributes);
        }

        if ($relatable) {
            $log->relatable()->associate($relatable);
        }

        $log->save();
    }

    /**
     * Get the related business ID.
     *
     * @return string
     */
    protected function getBusinessId() : string
    {
        return $this->business_id;
    }
}
