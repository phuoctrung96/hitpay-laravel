<?php

namespace App\Business;

use Illuminate\Database\Eloquent\Model;

class ChargeReceiptRecipient extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_charge_receipt_recipients';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $guarded = [
        //
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
