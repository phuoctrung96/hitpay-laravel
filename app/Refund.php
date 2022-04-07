<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'refunds';

    /**
     * Get the charge of this refund.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Charge
     */
    public function charge() : BelongsTo
    {
        return $this->belongsTo(Charge::class, 'charge_id', 'id', 'charge');
    }
}
